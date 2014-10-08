<?php
namespace wma\models;

use wma\models\User;
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
            [['username', 'password', 'password_confirm'], 'required'],
            [['password'], 'string', 'length' => [6, 50]],
            [['username'], 'string', 'length' => [3, 50]],
            [['first_name', 'last_name'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['email', 'first_name', 'last_name', 'username'], 'trim'],
            [['username'], 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => "{attribute} can contain only letters, numbers or underscores."],
            [['username'], 'unique', 'targetClass' => '\wma\models\User', 'message' => 'This username is already in use.'],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
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
     * Register new user.
     *
     * @param integer $status
     * @param integer $roleId
     * @return User|null the saved model or null if saving fails
     */
    public function registerUser() {
        if ($this->validate()) {
            $person = null;
            // Check if we have an existing Person model
            $existingPerson = Person::findOne(['email' => $this->email]);
             if (!is_null($existingPerson)) {
                 if ($existingPerson->first_name == $this->first_name && $existingPerson->last_name == $this->last_name) {
                     if (User::findOne($existingPerson->id)) {
                         // We already have a user entry for this person
                         $this->addError('email', 'This email address is already in use.');
                         return null;
                     } else {
                         // Existing person but no user record, use this person record
                         $person = $existingPerson;
                     }
                 } else {
                     // Name doesn't match but email already in use
                     $this->addError('email', 'This email address is already in use.');
                     return null;
                 }
             }

            // Create new Person model
            if (is_null($person)) {
                $person = new Person();
                $person->first_name = $this->first_name;
                $person->last_name = $this->last_name;
                $person->email = $this->email;
                if (!$person->save()) {
                    // Unable to create new Person model
                    $this->addError('email', 'This email address is already in use.');
                    return null;
                }
            }

            $user = new User();
            $user->person_id = $person->id;
            $user->username = $this->username;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->role_id = Yii::$app->getAdminModule()->getOption('userRegister', 'newUserRole');
            $user->status = Yii::$app->getAdminModule()->getOption('userRegister', 'newUserStatus');
            $user->created_at = Yii::$app->formatter->asMysqlDatetime();
            $user->save();
            return $user;
        }

        return null;
    }
}