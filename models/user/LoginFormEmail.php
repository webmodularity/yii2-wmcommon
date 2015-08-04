<?php

namespace wmc\models\user;

use Yii;

/**
 * FrontendLoginFormEmail
 */
class LoginFormEmail extends LoginForm
{
    public $email;
    public $password;
    public $rememberMe = true;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['email', 'password'], 'trim'],
            [['email', 'password'], 'required'],
            [['email', 'password'], 'string', 'max' => 255],
            [['email'], 'email'],
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels() {
        return [
            'email' => 'Email',
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
            $this->_user = User::find()->where(['email' => $this->email])->active()->one();
            if (is_null($this->_user)) {
                $this->_invalidUser = User::find()->where(['email' => $this->email])->inactive()->one();
            }
        }
        return $this->_user;
    }

    public function getInactiveUser() {
        return $this->_invalidUser;
    }

    protected function invalidLogin() {
        $this->email = $this->password = '';
        $this->addError('password', 'Unrecognized email/password combination.');
        $this->addError('email', 'Unrecognized email/password combination.');
    }
}