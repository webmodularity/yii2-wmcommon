<?php
namespace wmc\models;

use wmc\models\User;
use wmc\models\Person;
use yii\base\Model;
use Yii;

class RegisterForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $password_confirm;
    public $first_name;
    public $last_name;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email', 'first_name', 'last_name'], 'filter', 'filter' => 'trim'],
            [['username', 'email','password', 'password_confirm', 'first_name', 'last_name'], 'required'],
            ['username', 'string', 'min' => 3, 'max' => 50],
            ['password', 'string', 'min' => 6, 'max' => 50],
            [['first_name', 'last_name'], 'string', 'max' => 50],
            ['email', 'string', 'max' => 100],
            ['email', 'email'],
            ['username', 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => "{attribute} can contain only letters, numbers or underscores."],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
            ['email', 'unique', 'targetClass' => '\wmc\models\Person', 'message' => 'This email address is already in use.'],
            ['username', 'unique', 'targetClass' => '\wmc\models\User', 'message' => 'This username is already in use.'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Username',
            'password' => 'Password',
            'password_confirm' => 'Confirm Password',
            'email' => 'Email Address',
            'first_name' => 'First Name',
            'last_name' => 'Last Name'

        ];
    }


    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if ($this->validate()) {
            $user = new User();
            $person = new Person();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->save();
            return $user;
        }

        return null;
    }
}