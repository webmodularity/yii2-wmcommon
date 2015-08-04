<?php

namespace wmc\models\user;

use Yii;

/**
 * FrontendLoginFormEmail
 */
class LoginFormUsername extends LoginForm
{
    public $username;
    public $password;
    public $rememberMe = true;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username', 'password'], 'trim'],
            [['username', 'password'], 'required'],
            [['username', 'password'], 'string', 'max' => 255],
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
     * Finds user by [[email]]
     *
     * @return User|null
     */
    public function getUser() {
        if ($this->_user === false) {
            $this->_user = User::find()->where(['username' => $this->username])->active()->one();
            if (is_null($this->_user)) {
                $this->_invalidUser = User::find()->where(['username' => $this->username])->inactive()->one();
            }
        }
        return $this->_user;
    }

    public function getInactiveUser() {
        return $this->_invalidUser;
    }

    protected function invalidLogin() {
        $this->username = $this->password = '';
        $this->addError('password', 'Unrecognized username/password combination.');
        $this->addError('username', 'Unrecognized username/password combination.');
    }
}