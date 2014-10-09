<?php

namespace wmc\models;

use Yii;
use yii\base\Model;
/**
 * Forgot (username/password) form
 */
class ForgotUsernameForm extends Model
{
    public $email;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => 'Email Address'

        ];
    }
}