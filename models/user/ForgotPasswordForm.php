<?php

namespace wmc\models\user;

use Yii;
/**
 * Forgot (username/password) form
 */
class ForgotPasswordForm extends \yii\base\Model
{
    public $email;
    public $username;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'wmc\validators\OneRequiredValidator'],
            [['username'], 'string', 'length' => [3, 50]],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Username',
            'email' => 'Email Address'

        ];
    }
}