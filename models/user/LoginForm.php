<?php

namespace wmc\models\user;

use Yii;
use yii\base\InvalidConfigException;
use wmc\widgets\Alert;

/**
 * Login form
 */
class LoginForm extends \yii\base\Model
{
    // Default Session is 30 Days
    protected $_sessionDuration = 2592000;

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params) {
        if (!$this->hasErrors()) {
            if (is_null($this->user) || $this->user->validatePassword($this->$attribute) === false) {
                $this->invalidLogin();
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login() {
        if ($this->validate() && Yii::$app->user->login($this->user, $this->rememberMe ? $this->_sessionDuration : 0)) {
            return true;
        } else {
            // Get details of failed login reason
            $failedUser = $this->getFailedUser();
            if (is_null($failedUser)) {
                // No Username/Email record match
                UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN, UserCooldownLog::RESULT_NO_RECORD);
            } else if ($failedUser->status == User::STATUS_NEW) {
                // Account is flagged as NEW
                UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_NEW, $failedUser->id);
                UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN, UserCooldownLog::RESULT_NEW);
                Yii::$app->alertManager->add(Alert::widget([
                    'heading' => 'Account Not Active.',
                    'message' => 'This account is pending approval. You will receive an email if further action is required.',
                    'style' => 'warning',
                    'icon' => 'warning'
                ]));
            } else if ($failedUser->status == User::STATUS_DELETED) {
                // Account is flagged as DELETED
                UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_DELETED, $failedUser->id);
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
                UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_FAIL, $failedUser->id);
            }
            return false;
        }
    }

    public function getUser() {
        throw new InvalidConfigException("LoginForm should not be used directly!!");
    }

    public function getFailedUser() {
        throw new InvalidConfigException("LoginForm should not be used directly!!");
    }

    protected function invalidLogin() {
        throw new InvalidConfigException("LoginForm should not be used directly!!");
    }

    public function setSessionDuration($seconds) {
        if (is_int($seconds) && $seconds >= 0) {
            $this->_sessionDuration = $seconds;
        }
    }

    public function getSessionDuration() {
        return $this->_sessionDuration;
    }
}