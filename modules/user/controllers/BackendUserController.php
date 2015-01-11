<?php

namespace wmu\controllers;

use wmu\models\UserCooldownLog;
use Yii;
use yii\helpers\Url;
use wmc\helpers\Html;
use wmc\helpers\ArrayHelper;
use wmu\models\LoginForm;
use wmu\models\RegisterForm;
use wmu\models\ForgotPasswordForm;
use wmu\models\ForgotUsernameForm;
use wmu\models\User;
use wmu\models\UserKey;
use wmu\models\UserLog;
use wmu\models\UserCooldown;

class BackendUserController extends \yii\web\Controller
{
    public $viewFileLogin = '@backend/views/user/login';
    public $viewFileForgotPassword = '@backend/views/user/forgot-password';
    public $viewFileForgotUsername = '@backend/views/user/forgot-username';
    public $viewFileRegister = '@backend/views/user/register';

    public $emailData = [
        'confirm-email' => [
            'title' => "Confirm Email",
            'subject' => "User Email Confirmation"
        ]
    ];

    public function actionError() {
        Yii::$app->alertManager->add(
            'danger',
            Yii::$app->errorHandler->exception->getMessage(),
            Yii::$app->errorHandler->exception->getName(),
            ['block' => true]
        );
        $this->redirect(['/user/login']);
    }

    public function actionLogin() {
        $model = new LoginForm();
        if (!Yii::$app->user->isGuest) {
            // Already logged in
            return $this->goHome();
        } else if (UserCooldown::IPOnCooldown(Yii::$app->request->userIP) === true) {
            // IP is on cooldown
            static::addCooldownAlert();
            return $this->render($this->viewFileLogin, ['model' => $model]);
        } else if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Successful login
            UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_SUCCESS);
            return $this->goBack();
        } else {
            // Failed login
            Yii::$app->session->set('wmu.cooldown_count', Yii::$app->session->get('wmu.cooldown_count', 0) + 1);
            if (Yii::$app->session->get('wmu.cooldown_count', 0) == UserCooldownLog::$cooldownThreshold) {
                static::addCooldownAlert();
            } else if (Yii::$app->session->get('wmu.cooldown_count', 0) >= (UserCooldownLog::$cooldownThreshold - 2)) {
                static::addCooldownWarningAlert();
            }
            return $this->render($this->viewFileLogin,['model' => $model]);
        }
    }

    public function actionForgotPassword($key = null) {
        $model = new ForgotPasswordForm();
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        } else if (UserCooldown::IPOnCooldown(Yii::$app->request->userIP) === true) {
            // IP is on cooldown
            static::addCooldownAlert();
        } else if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $user = !$model->email ? User::findByUsername($model->username) : User::findByEmail($model->email);
            if (!is_null($user)) {
                $userKey = UserKey::generateKey($user->person_id, UserKey::TYPE_RESET_PASSWORD);
                UserLog::add(UserLog::ACTION_RESET_PASSWORD, UserLog::RESULT_REQUEST, $user->person_id);
                // Generate Email
                $this->sendEmail('reset-password', $user->person->email,
                    [
                        'link' => Url::toRoute(['/user/forgot-password', 'key' => $userKey->user_key], true),
                    ]
                );
                Yii::$app->alertManager->add(
                    'success',
                    'An email has been sent to the registered email address with instructions on how to reset
                     your password. Further action is required, check your email.',
                    'Password Reset Request Sent.',
                    ['icon' => 'hand-o-right']
                );
            } else {
                $failedReason = !$model->email
                    ? UserCooldownLog::ACTION_RESET_PASSWORD_USER
                    : UserCooldownLog::ACTION_RESET_PASSWORD_EMAIL;
                $failedData = !$model->email ? $model->username : $model->email;
                UserCooldownLog::add(UserCooldownLog::ACTION_RESET_PASSWORD);
                Yii::$app->alertManager->add(
                    'danger',
                    'Failed to send password reset request, unable to locate user.',
                    'No Account Found!',
                    ['icon' => 'ban']
                );
            }

            return Yii::$app->response->refresh();
        }
        return $this->render($this->viewFileForgotPassword, ['model' => $model]);
    }

    public function actionForgotUsername() {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new ForgotUsernameForm();
        if ($model->load(Yii::$app->request->post())) {
            // handle form
        }
        return $this->render($this->viewFileForgotUsername, [
                'model' => $model,
            ]);
    }

    public function actionRegister() {
        $model = new RegisterForm();
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        } else if (Yii::$app->adminSettings->getOption('user.register.webRegistration') !== true) {
            throw new \yii\web\HttpException(404, 'Registration is not allowed.');
        } else if (UserCooldown::IPOnCooldown(Yii::$app->request->userIP) === true) {
            // IP is on cooldown
            static::addCooldownAlert();
        } else if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->registerUser(
                Yii::$app->adminSettings->getOption('user.register.newUserRole'),
                Yii::$app->adminSettings->getOption('user.register.newUserStatus')
            )) {
                if (Yii::$app->adminSettings->getOption('user.register.confirmEmail') === true) {
                    $userKey = UserKey::generateKey($user->person_id, UserKey::TYPE_CONFIRM_EMAIL);
                    $this->sendConfirmEmail($user->person->email, $userKey->user_key);
                }

                if (Yii::$app->getUser()->login($user)) {
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_SUCCESS);
                    return $this->goHome();
                }
            }
        }
        return $this->render($this->viewFileRegister, ['model' => $model]);
    }

    public function actionConfirm($key) {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        } else if (Yii::$app->adminSettings->getOption('user.register.webRegistration') !== true
            || Yii::$app->adminSettings->getOption('user.register.confirmEmail') !== true
            || !$key || !is_string($key) || strlen($key) != 32
        ) {
            throw new \yii\web\HttpException(404, 'Invalid parameters passed to confirm email page.');
        } else if (UserCooldown::IPOnCooldown(Yii::$app->request->userIP) === true) {
            // IP is on cooldown
            static::addCooldownAlert();
            return Yii::$app->response->redirect(Yii::$app->user->loginUrl);
        }

        $userKey = UserKey::findByKey($key, UserKey::TYPE_CONFIRM_EMAIL);
        if (is_null($userKey)) {
            // See if we can find key but it's expired
            $expiredKey = UserKey::fndByKey($key, UserKey::TYPE_CONFIRM_EMAIL, true);
            if (!is_null($expiredKey)) {
                // The key exists but has expired, generate new key and send email
                UserCooldownLog::add(UserCooldownLog::ACTION_CONFIRM_EMAIL_EXPIRED_KEY);
                Yii::$app->alertManager->add(
                    'warning',
                    'This confirmation key has expired. A new confirmation link has been sent to you via email. '
                    . 'Please follow the confirmation instructions in that email to continue.',
                    'Expired User Key!'
                );
            } else {
                UserCooldownLog::add(UserCooldownLog::ACTION_CONFIRM_EMAIL_BAD_KEY);
                Yii::$app->alertManager->add(
                    'danger',
                    'Cannot confirm email address as the specified user key could not be located in our database.',
                    'Unrecognized User Key!',
                    ['icon' => 'ban']
                );
            }
        }
    }

    public function actionLogout() {
        UserLog::add(UserLog::ACTION_LOGOUT, UserLog::RESULT_SUCCESS);
        Yii::$app->user->logout();
        Yii::$app->alertManager->add(
            'success',
            'User session cleared.',
            'Successfully Logged Out',
            ['icon' => 'sign-out']
        );
        return Yii::$app->response->redirect(Yii::$app->user->loginUrl);
    }

    public static function addCooldownAlert() {
        Yii::$app->alertManager->add(
            'danger',
            'The system has detected too many failed login attempts from '
            . 'this location and has temporarily locked it, preventing any user related activity.',
            'Account Locked!',
            ['icon' => 'ban']
        );
    }

    public static function addCooldownWarningAlert() {
        Yii::$app->alertManager->add(
            'warning',
            'The system has detected a high number of failed user actions from your location.'
            .' If you need further assistance please '
            . Html::mailto('email', Yii::$app->params['adminEmail'], ['class' => 'alert-link'])
            . ' the site administrator.',
            'Account Lock Warning!',
            ['icon' => 'warning', 'encodeMessage' => false]
        );
    }

    protected function sendConfirmEmail($to, $key) {
        $params =
            [
                'key' => $key,
                'title' => $this->emailData['confirm-email']['title'],
                'emailAddress' => $to
            ];
        Yii::$app->mailer->compose('@wma/mail/confirm-email', $params)
            ->setFrom(Yii::$app->params['noReplyEmail'])
            ->setTo($to)
            ->setSubject($this->emailData['confirm-email']['subject'])
            ->send();
    }

}