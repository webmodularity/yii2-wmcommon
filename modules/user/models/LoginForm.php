<?php

namespace wmu\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;
    private $_sessionDuration = 14400;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels() {
        return [
            'username' => 'Username',
            'password' => 'Password',
            'rememberMe' => 'Remember Me?'

        ];
    }
    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params) {
        if (!$this->hasErrors()) {
            $user = User::findOne(['username' => $this->username]);
            if (is_null($user)) {
                UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN_USER);
                $this->invalidLogin();
            } else {
                if ($user->status == User::STATUS_NEW) {
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_FAIL, $user->id);
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN_USER);
                    Yii::$app->alertManager->add(
                        'warning',
                        'This account is pending approval. You will receive an email if further action is required.',
                        'Account Not Active.'
                    );
                    $this->invalidLogin();
                } else if ($user->status == User::STATUS_DELETED) {
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_FAIL, $user->id);
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN_USER);
                    Yii::$app->alertManager->add(
                        'danger',
                        'This account has been removed. Please contact support for further assistance. ',
                        'Account Removed/Banned!'
                    );
                    $this->invalidLogin();
                } else if ($user->validatePassword($this->password) === false) {
                    UserCooldownLog::add(UserCooldownLog::ACTION_LOGIN_PASS);
                    UserLog::add(UserLog::ACTION_LOGIN, UserLog::RESULT_FAIL, $user->person_id);
                    $this->invalidLogin();
                }
            }
        }
    }

    protected function invalidLogin() {
        $this->username = $this->password = '';
        $this->addError('password', 'Unrecognized username/password combination.');
        $this->addError('username', 'Unrecognized username/password combination.');
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login() {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->_sessionDuration);
        } else {
            return false;
        }
    }
    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser() {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }

    public function setSessionDuration($seconds) {
        if (is_int($seconds) && $seconds >= 0) {
            $this->_sessionDuration = $seconds;
        }
    }
}