<?php

namespace wmc\controllers;

use Yii;
use wmc\filters\IpCooldownFilter;
use wmc\models\user\UserCooldownLog;
use wmc\models\user\UserLog;
use wmc\models\user\UserKey;
use wmc\models\user\LoginFormEmail;
use wmc\models\user\LoginFormUsername;
use wmc\models\user\ForgotPasswordEmailForm;
use wmc\models\user\ForgotPasswordForm;
use wmc\models\user\User;
use wmc\models\user\ResetPasswordForm;
use yii\helpers\Html;
use wmc\filters\AccessControl;
use yii\base\InvalidParamException;
use yii\web\ForbiddenHttpException;
use wmc\widgets\Alert;

class UserController extends \yii\web\Controller
{
    protected $_userType = 'username';
    /**
     * Used to set view files for login, forgotPassword, and resetPassword actions.
     * @var array
     */
    public $viewFile = [
        'login' => 'login',
        'error' => 'error',
        'forgotPassword' => 'forgot-password',
        'resetPassword' => 'reset-password'
    ];
    /**
     * Where to send browser after successful login, defaults to null.
     * @var string|array|null Non-null values will be passed to yii\web\Controller::redirect($url),
     * A null value (default) returns to last page using yii\web\Controller::goBack()
     */
    public $loginRedirect = null;


    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => [
                    'login',
                    'logout',
                    'register',
                    'forgot-password',
                    'reset-password',
                    'dashboard',
                    'settings',
                    'index'
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login', 'register', 'forgot-password', 'reset-password'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['logout', 'dashboard', 'settings'],
                        'roles' => ['@'],
                    ]
                ],
            ],
            'cooldown' => [
                'class' => IpCooldownFilter::className(),
                'only' => ['login', 'register', 'forgot-password','reset-password']
            ],
        ];
    }

    public function setUserType($userType) {
        if (in_array(strtolower($userType), ['username','email'])) {
            $this->_userType = strtolower($userType);
        }
    }

    public function getUserType() {
        return $this->_userType;
    }

    public function actionLogin() {
        $model = $this->userType === 'username' ? new LoginFormUsername() : new LoginFormEmail();
        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->login()) {
                // Successful login
                UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_SUCCESS);
                if (is_null($this->loginRedirect)) {
                    return $this->goBack();
                } else {
                    return $this->redirect($this->loginRedirect);
                }
            } else {
                // Bad User/Pass combo
                // Warn if cooldown is reached or approaching
                $cooldownCount = UserCooldownLog::getCooldownCount();
                if ($cooldownCount >= (UserCooldownLog::$cooldownThreshold - 2)) {
                    static::addCooldownWarningAlert();
                }
                // Get details of failed login reason
                $user = $model->getInactiveUser();
                if (is_null($user)) {
                    // No Username/Email record match
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN, UserCooldownLog::RESULT_NO_RECORD);
                } else if ($user->status == User::STATUS_NEW) {
                    // Account is flagged as NEW
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_NEW, $user->id);
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN, UserCooldownLog::RESULT_NEW);
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Account Not Active.',
                        'message' => 'This account is pending approval. You will receive an email if further action is required.',
                        'style' => 'warning',
                        'icon' => 'warning'
                    ]));
                } else if ($user->status == User::STATUS_DELETED) {
                    // Account is flagged as DELETED
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_DELETED, $user->id);
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN, UserCooldownLog::RESULT_DELETED);
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Account Removed/Banned!',
                        'message' => 'This account has been removed. Please contact support for further assistance.',
                        'style' => 'danger',
                        'icon' => 'ban'
                    ]));
                } else {
                    // Must be bad password
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN, UserCooldownLog::RESULT_BAD_PASSWORD);
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_FAIL, $user->id);
                }
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
            'style' => 'success',
            'icon' => 'sign-out'
        ]));
        return $this->goHome();
    }

    public function actionForgotPassword() {
        $model = $this->userType === 'username' ? new ForgotPasswordForm() : new ForgotPasswordEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($this->userType === 'username') {
                $user = User::find()->where(['username' => $model->username])->active()->one();
            } else {
                $user = User::find()->where(['email' => $model->email])->active()->one();
            }

            if (!is_null($user)) {
                // Lets make sure we don't have any recent password resets for this user
                $recentRequests = UserLog::find()->recent("PT2M")->andWhere(
                    [
                        'user_id' => $user->id,
                        'app' => UserLog::APP_FRONTEND,
                        'action_type' => UserLog::ACTION_RESET_PASSWORD,
                        'result_type' => UserLog::RESULT_REQUEST
                    ])->count();
                if ($recentRequests < 1) {
                    $userKey = UserKey::generateKey($user->id, UserKey::TYPE_RESET_PASSWORD);
                    UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_REQUEST, $user->id);
                    // Generate Email
                    $this->sendResetPasswordEmail($user, $userKey);
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Password Reset Request Sent.',
                        'message' => 'An email has been sent to the registered email address with instructions on how to reset
                        your password. Further action is required, please check your email.',
                        'style' => 'success',
                        'icon' => 'hand-o-right'
                    ]));
                } else {
                    UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_COOLDOWN, $user->id);
                    UserCooldownLog::add($this->getFailedReason($model), UserCooldownLog::RESULT_COOLDOWN);
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Password Request Email Already Sent!',
                        'message' => "The system has detected a recent password reset request for this account. Please check your
                        email (make sure it didn't end up in spam folder) for the password reset instructions.",
                        'style' => 'warning',
                        'icon' => 'warning'
                    ]));
                }
            } else {
                UserCooldownLog::add($this->getFailedReason($model), UserCooldownLog::RESULT_FAIL);
                Yii::$app->alertManager->add(Alert::widget([
                    'heading' => 'No Account Found!',
                    'message' => 'Failed to send password reset request, unable to locate user.',
                    'style' => 'danger',
                    'icon' => 'times'
                ]));
            }
            return Yii::$app->response->refresh();
        }
        return $this->render($this->viewFile['forgotPassword'], ['model' => $model]);
    }

    private function getFailedReason($model) {
        if ($this->userType === 'username') {
            $failedReason = !$model->email
                ? UserCooldownLog::ACTION_RESET_PASSWORD_USER
                : UserCooldownLog::ACTION_RESET_PASSWORD_EMAIL;
        } else {
            $failedReason = UserCooldownLog::ACTION_RESET_PASSWORD_EMAIL;
        }
        return $failedReason;
    }

    public function actionResetPassword($key) {
        if (UserKey::isValidKey($key)) {
            try {
                $model = new ResetPasswordForm($key);
            } catch (InvalidParamException $e) {
                UserCooldownLog::add($this->getFailedReason($model), UserCooldownLog::RESULT_FAIL);
                Yii::$app->alertManager->add(Alert::widget([
                    'heading' => 'Password Reset Failed!',
                    'message' => "Failed to complete password reset. Your password reset link may have expired. You may try and
                    use the ".Html::a('Forgot Password Tool', ['forgot-password'], ['class' => 'alert-link'])." again
                    to generate a new link.",
                    'style' => 'danger',
                    'icon' => 'times',
                    'encode' => false
                ]));
                return $this->redirect(Yii::$app->user->loginUrl);
            }

            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                $reset = $model->resetPassword();
                if ($reset) {
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Password Reset Successful!',
                        'message' => 'Your password has been reset, you may now log in using your new password.',
                        'style' => 'success',
                        'icon' => 'hand-o-right'
                    ]));
                    $this->sendResetPasswordSuccessEmail($model->user);
                } else {
                    Yii::$app->alertManager->add(Alert::widget([
                        'heading' => 'Password Reset Failed!',
                        'message' => 'Failed to complete password reset, please contact us for further assistance.',
                        'style' => 'danger',
                        'icon' => 'times'
                    ]));
                }
                return $this->redirect(Yii::$app->user->loginUrl);
            }
            return $this->render($this->viewFile['resetPassword'], ['model' => $model]);
        } else {
            throw new ForbiddenHttpException("Unrecognized user key specified!");
        }
    }

    public static function addCooldownAlert() {
        Yii::$app->alertManager->add(Alert::widget([
            'heading' => "Account Locked!",
            'message' => 'The system has detected too many failed login attempts from '
                . 'this location and has temporarily locked it, preventing any user related activity.',
            'style' => 'danger',
            'icon' => 'ban'
        ]));
    }

    public static function addCooldownWarningAlert() {
        Yii::$app->alertManager->add(Alert::widget([
            'heading' => "Account Lock Warning!",
            'message' => 'The system has detected a high number of failed user actions from your location.'
                .' If you need further assistance please contact the site administrator. Further failed attempts
                will result in your account being locked.',
            'style' => 'warning',
            'icon' => 'warning',
            'encode' => false,
        ]));
    }

    protected function sendResetPasswordEmail($user, $userKey) {
        Yii::$app->mailer->compose('@wmu/mail/reset-password', ['user' => $user, 'userKey' => $userKey])
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($user->email)
            ->setSubject(Yii::$app->params['siteName'] . ' Password Reset Request')
            ->send();
    }

    protected function sendResetPasswordSuccessEmail($user) {
        Yii::$app->mailer->compose('@wmu/mail/reset-password-success', ['user' => $user])
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($user->email)
            ->setSubject(Yii::$app->params['siteName'] . ' Password Reset Success')
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
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}