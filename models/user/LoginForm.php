<?php

namespace wmc\models\user;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Login form
 */
class LoginForm extends \yii\base\Model
{
    protected $_user = false;
    protected $_invalidUser = null;
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
        if ($this->validate()) {
            return Yii::$app->user->login($this->user, $this->rememberMe ? $this->_sessionDuration : 0);
        } else {
            return false;
        }
    }

    public function getUser() {
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