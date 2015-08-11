<?php

namespace wmc\controllers;

use Yii;
use wmc\filters\IpCooldownFilter;
use wmc\models\user\UserCooldownLog;
use wmc\models\user\UserLog;
use wmc\models\user\UserKey;
use wmc\models\user\LoginFormEmail;
use wmc\models\user\User;
use wmc\models\user\ResetPasswordForm;
use yii\helpers\Html;
use wmc\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use wmc\widgets\Alert;
use yii\helpers\Json;

class UserController extends \yii\web\Controller
{
    /**
     * Used to set view files for login, forgotPassword, and resetPassword actions.
     * @var array
     */
    public $viewFile = [
        'login' => 'login',
        'error' => 'error',
        'forgotPassword' => 'forgot-password',
        'resetPassword' => 'reset-password',
        'changePassword' => 'change-password',
        'changeEmail' => 'change-email'
    ];


    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login', 'register', 'forgot-password', 'reset-password', 'reset-email'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['logout', 'forgot-password', 'reset-password', 'profile', 'change-password', 'change-email', 'reset-email'],
                        'roles' => ['@'],
                    ]
                ],
            ],
            'cooldown' => [
                'class' => IpCooldownFilter::className(),
                'only' => ['login', 'register', 'forgot-password','reset-password', 'change-password', 'change-email', 'reset-email']
            ],
        ];
    }

    public function actionLogin() {
        $model = new LoginFormEmail();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Successful login
            UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_SUCCESS);
            return $this->redirect(Yii::$app->user->returnUrl);
        } else {
            // Bad User/Pass combo
            // Warn if cooldown is reached or approaching
            $cooldownCount = UserCooldownLog::getCooldownCount();
            if ($cooldownCount >= (UserCooldownLog::$cooldownThreshold - 2)) {
                static::addCooldownWarningAlert();
            }
        }
        return $this->render($this->viewFile['login'],['model' => $model]);
    }

    public function actionLogout() {
        UserLog::add(UserLog::ACTION_LOGOUT, UserLog::RESULT_SUCCESS);
        Yii::$app->user->logout();
        Yii::$app->alertManager->add(Alert::widget([
            'heading' => 'User session cleared.',
            'message' => 'Successfully Logged Out',
            'style' => 'success'
        ]));
        return $this->goHome();
    }

    public function actionForgotPassword() {
        $model = new User(['scenario' => 'forgotPassword']);
        if (!Yii::$app->user->isGuest) {
            $model->email = Yii::$app->user->identity->email;
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate(['email'])) {
            $user = User::find()->where(['email' => $model->email])->active()->one();

            if (!is_null($user)) {
                // Lets make sure we don't have any recent password resets for this user
                $recentRequests = UserLog::find()->recent("PT2M")->andWhere(['user_id' => $user->id,
                    'action_type' => UserLog::ACTION_RESET_PASSWORD, 'result_type' => UserLog::RESULT_REQUEST
                ])->count();
                if ($recentRequests < 1) {
                    $userKey = new UserKey([
                        'user_id' => $user->id,
                        'type' => UserKey::TYPE_RESET_PASSWORD,
                        'user_key' => UserKey::generateKey()
                    ]);
                    if ($userKey->save()) {
                        UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_REQUEST, $user->id);
                        // Generate Email
                        $this->sendResetPasswordEmail($user, $userKey->user_key);
                        Yii::$app->alertManager->add(Alert::widget([
                            'heading' => 'Password Reset Request Sent.',
                            'message' => 'An email has been sent to the registered email address with instructions on how to reset
                        your password. Further action is required, please check your email.',
                            'style' => 'success'
                        ]));
                    } else {
                        UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_FAIL, $user->id);
                        Yii::$app->alertManager->add(Alert::widget([
                            'heading' => 'Failed to Send Password Reset Request.',
                            'message' => 'We have encountered an error while attempting to send reset password email.
                            Sorry for the inconvenience, please contact us for further assistance.',
                            'style' => 'danger'
                        ]));
                    }
                } else {
                    UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_COOLDOWN, $user->id);
                    UserCooldownLog::add(UserCooldownLog::ACTION_RESET_PASSWORD, UserCooldownLog::RESULT_COOLDOWN);
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Password Request Email Already Sent!',
                        'message' => "The system has detected a recent password reset request for this account. Please check your
                        email (make sure it didn't end up in spam folder) for the password reset instructions.",
                        'style' => 'warning'
                    ]));
                }
            } else {
                UserCooldownLog::add(UserCooldownLog::ACTION_RESET_PASSWORD, UserCooldownLog::RESULT_FAIL);
                Yii::$app->alertManager->add(Alert::widget([
                    'heading' => 'No Account Found!',
                    'message' => 'Failed to send password reset request, unable to locate user.',
                    'style' => 'danger'
                ]));
            }
            return $this->goHome();
        }
        return $this->render($this->viewFile['forgotPassword'], ['model' => $model]);
    }

    public function actionResetPassword($key) {
        $userKey = new UserKey(['user_key' => $key]);
        if (!empty($key) && $userKey->validate(['user_key'])) {
            $user = User::findByResetPasswordKey($key);
            if (is_null($user)) {
                UserCooldownLog::add(UserCooldownLog::ACTION_RESET_PASSWORD, UserCooldownLog::RESULT_FAIL);
                Yii::$app->alertManager->add(Alert::widget([
                    'heading' => 'Password Reset Failed!',
                    'message' => "Failed to complete password reset. Your password reset link may have expired. You may try and
                    use the " . Html::a('Forgot Password Tool', ['forgot-password'], ['class' => 'alert-link']) . " again
                    to generate a new link.",
                    'style' => 'danger',
                    'encode' => false
                ]));
                return $this->goHome();
            } else {
                $userModel = new User(['scenario' => 'resetPassword']);
                if (!Yii::$app->user->isGuest) {
                    Yii::$app->user->logout();
                }
                if ($userModel->load(Yii::$app->request->post()) && $userModel->validate(['password', 'password_confirm'])) {
                    $user->setPassword($userModel->password);
                    UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_SUCCESS, $user->id);
                    $user->resetPasswordUserKey->delete();
                    if ($user->save()) {
                        $this->sendResetPasswordSuccessEmail($user);
                        Yii::$app->alertManager->add(Alert::widget([
                            'heading' => 'Password Reset Successful!',
                            'message' => 'Your password has been reset, you may now log in using your new password.',
                            'style' => 'success'
                        ]));
                    } else {
                        Yii::$app->alertManager->add(Alert::widget([
                            'heading' => 'Password Reset Failed!',
                            'message' => 'Failed to complete password reset, please contact us for further assistance.',
                            'style' => 'danger'
                        ]));
                    }
                    return $this->redirect(Yii::$app->user->loginUrl);
                }

                return $this->render($this->viewFile['resetPassword'], ['model' => $userModel]);
            }
        } else {
            UserCooldownLog::add(UserCooldownLog::ACTION_RESET_PASSWORD, UserCooldownLog::RESULT_FAIL);
            Yii::error("UserKey was invalid (".Html::encode($key).") on reset password request!");
            throw new ForbiddenHttpException("Unrecognized user key specified!");
        }
    }

    public function actionChangePassword() {
        $user = $this->findModel(Yii::$app->user->id);
        $model = new User(['scenario' => 'changePassword']);
        if ($model->load(Yii::$app->request->post()) && $model->validate(['old_password', 'password', 'password_confirm'])) {
            if ($user->validatePassword($model->old_password)) {
                $user->setPassword($model->password);
                if ($user->save(false)) {
                    UserLog::add(UserLog::ACTION_CHANGE_PASSWORD, UserLog::RESULT_SUCCESS);
                    Yii::$app->user->logout();
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Change Password Successful!',
                        'message' => 'Your password has been changed, you may now log in using your new password.',
                        'style' => 'success'
                    ]));
                    return $this->redirect(Yii::$app->user->loginUrl);
                }
            } else {
                UserCooldownLog::add(UserCooldownLog::ACTION_CHANGE_PASSWORD, UserCooldownLog::RESULT_BAD_PASSWORD);
                $cooldownCount = UserCooldownLog::getCooldownCount();
                if ($cooldownCount >= (UserCooldownLog::$cooldownThreshold - 2)) {
                    static::addCooldownWarningAlert();
                }
                $model->addError('old_password', "Password did not match!");
                $model->old_password = '';
            }
            UserLog::add(UserLog::ACTION_CHANGE_PASSWORD, UserLog::RESULT_FAIL);
            Yii::$app->alertManager->add(Alert::widget([
                'heading' => 'Change Password Failed!',
                'message' => 'Failed to complete password change.',
                'style' => 'danger'
            ]));
        }
        return $this->render($this->viewFile['changePassword'], ['model' => $model]);
    }

    public function actionChangeEmail() {
        $user = $this->findModel(Yii::$app->user->id);
        $model = new User(['scenario' => 'changeEmail']);
        if ($model->load(Yii::$app->request->post()) && $model->validate(['old_password', 'email', 'email_confirm'])) {
            if ($user->validatePassword($model->old_password)) {
                $key = new UserKey([
                    'user_id' => $user->id,
                    'type' => UserKey::TYPE_CHANGE_EMAIL,
                    'user_key' => UserKey::generateKey()
                ]);
                if ($key->save()) {
                    UserLog::add(UserLog::ACTION_CHANGE_EMAIL, UserLog::RESULT_REQUEST, $user->id, Json::encode(['key' => $key->user_key, 'email' => $model->email]));
                    $this->sendChangeEmailConfirm($user, $key->user_key, $model->email);
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Change Email Request Sent!',
                        'message' => "Your request to change your email address has been received. Please check your new
                         email (".$model->email.") for instructions on finalizing your email address change, further action
                        is required.",
                        'style' => 'success'
                    ]));
                    return $this->redirect(['profile']);
                }
            } else {
                UserCooldownLog::add(UserCooldownLog::ACTION_CHANGE_EMAIL, UserCooldownLog::RESULT_BAD_PASSWORD);
                $cooldownCount = UserCooldownLog::getCooldownCount();
                if ($cooldownCount >= (UserCooldownLog::$cooldownThreshold - 2)) {
                    static::addCooldownWarningAlert();
                }
                $model->addError('old_password', "Password did not match!");
                $model->old_password = '';
            }

            UserLog::add(UserLog::ACTION_CHANGE_EMAIL, UserLog::RESULT_FAIL);
            Yii::$app->alertManager->add(Alert::widget([
                'heading' => 'Change Email Failed!',
                'message' => 'Failed to complete email change.',
                'style' => 'danger'
            ]));
        }
        return $this->render($this->viewFile['changeEmail'], ['model' => $model]);
    }

    public function actionResetEmail($key) {
        $userKey = new UserKey(['user_key' => $key]);
        if (!empty($key) && $userKey->validate(['user_key'])) {
            $user = User::findByChangeEmailKey($key);
            if (is_null($user)) {
                UserCooldownLog::add(UserCooldownLog::ACTION_CHANGE_EMAIL, UserCooldownLog::RESULT_FAIL);
                Yii::$app->alertManager->add(Alert::widget([
                    'heading' => 'Confirm New Email Failed!',
                    'message' => "Failed to complete new email change. Your email confirm link may have expired. You may try and
                    use the " . Html::a('Change Email Tool', ['change-email'], ['class' => 'alert-link']) . " again
                    to generate a new link.",
                    'style' => 'danger',
                    'encode' => false
                ]));
                return $this->goHome();
            } else {
                $log = UserLog::find()->where([
                    'user_id' => $user->id,
                    'action_type' => UserLog::ACTION_CHANGE_EMAIL,
                    'result_type' => UserLog::RESULT_REQUEST
                ])->orderBy(['id' => SORT_DESC])->limit(1)->one();
                $logData = Json::decode($log->data, true);
                if (!Yii::$app->user->isGuest) {
                    Yii::$app->user->logout();
                }
                if (isset($logData['key']) && $logData['key'] == $key && isset($logData['email']) && !empty($logData['email'])) {
                    $user->email = $logData['email'];
                    if ($user->save()) {
                        $this->sendRemovedEmail($user);
                        UserLog::add(UserLog::ACTION_CHANGE_EMAIL, UserLog::RESULT_SUCCESS, $user->id, "New Email: ".$user->email."");
                        Yii::$app->alertManager->add(Alert::widget([
                            'heading' => 'Email Change Successful!',
                            'message' => 'Your email address has been changed, you may now log in using your new email.',
                            'style' => 'success'
                        ]));
                    } else {
                        UserCooldownLog::add(UserCooldownLog::ACTION_CHANGE_EMAIL, UserCooldownLog::RESULT_FAIL);
                        UserLog::add(UserLog::ACTION_CHANGE_EMAIL, UserLog::RESULT_FAIL, $user->id, "Failed to save User!");
                        Yii::$app->alertManager->add(Alert::widget([
                            'heading' => 'Email Change Failed!',
                            'message' => 'Failed to complete email change, please contact us for further assistance.',
                            'style' => 'danger'
                        ]));
                    }
                } else {
                    UserCooldownLog::add(UserCooldownLog::ACTION_CHANGE_EMAIL, UserCooldownLog::RESULT_FAIL);
                    UserLog::add(UserLog::ACTION_CHANGE_EMAIL, UserLog::RESULT_FAIL, $user->id, "UserKey did not match key in UserLog!");
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Email Change Failed!',
                        'message' => 'Failed to complete email change, it is possible the request has expired.',
                        'style' => 'danger'
                    ]));
                }

                return $this->redirect(Yii::$app->user->loginUrl);
            }
        } else {
            UserCooldownLog::add(UserCooldownLog::ACTION_CHANGE_EMAIL, UserCooldownLog::RESULT_FAIL);
            Yii::error("UserKey was invalid (".Html::encode($key).") on reset email request!");
            throw new ForbiddenHttpException("Unrecognized user key specified!");
        }
    }

    public static function addCooldownAlert() {
        Yii::$app->alertManager->add(Alert::widget([
            'heading' => "Account Locked!",
            'message' => 'The system has detected too many failed login attempts from '
                . 'this location and has temporarily locked it, preventing any user related activity.',
            'style' => 'danger'
        ]));
    }

    public static function addCooldownWarningAlert() {
        Yii::$app->alertManager->add(Alert::widget([
            'heading' => "Account Lock Warning!",
            'message' => 'The system has detected a high number of failed user actions from your location.'
                .' If you need further assistance please contact the site administrator. Further failed attempts
                could result in your account being temporarily locked.',
            'style' => 'warning',
            'encode' => false,
        ]));
    }

    protected function sendResetPasswordEmail($user, $userKey) {
        Yii::$app->mailer->compose('@wmc/mail/user/reset-password', ['user' => $user, 'userKey' => $userKey])
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($user->email)
            ->setSubject(Yii::$app->params['siteName'] . ' Password Reset Request')
            ->send();
    }

    protected function sendResetPasswordSuccessEmail($user) {
        Yii::$app->mailer->compose('@wmc/mail/user/reset-password-success', ['user' => $user])
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($user->email)
            ->setSubject(Yii::$app->params['siteName'] . ' Password Reset Success')
            ->send();
    }

    protected function sendChangeEmailConfirm($user, $userKey, $newEmail) {
        Yii::$app->mailer->compose('@wmc/mail/user/change-email', ['user' => $user, 'userKey' => $userKey, 'newEmail' => $newEmail])
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($newEmail)
            ->setSubject(Yii::$app->params['siteName'] . ' Change Email Request')
            ->send();
    }

    protected function sendRemovedEmail($user) {
        Yii::$app->mailer->compose('@wmc/mail/user/removed-email', ['user' => $user])
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($user->email)
            ->setSubject(Yii::$app->params['siteName'] . ' Email Address Removed')
            ->send();
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = User::find()->where([User::tableName().'.id' => $id])->active()->one()) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}