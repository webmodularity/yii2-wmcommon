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
     * Finds ACTIVE user by [[email]]
     *
     * @return User|null
     */
    public function getUser() {
        return User::find()->where(['email' => $this->email])->active()->one();
    }

    /**
     * Finds ANY user (active or disabled) by [[email]]
     *
     * @return User|null
     */

    public function getFailedUser() {
        return User::find()->where(['email' => $this->email])->one();
    }

    protected function invalidLogin() {
        $this->password = '';
        $this->addError('password', 'Unrecognized email/password combination.');
        $this->addError('email', 'Unrecognized email/password combination.');
    }
}