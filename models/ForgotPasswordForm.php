<?php

namespace wma\models;

use Yii;
use yii\base\Model;
/**
 * Forgot (username/password) form
 */
class ForgotPasswordForm extends Model
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