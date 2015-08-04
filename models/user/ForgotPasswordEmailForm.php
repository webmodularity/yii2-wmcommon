<?php

namespace wmc\models\user;

use Yii;
/**
 * Forgot (password) form used with Email only login schemes
 */
class ForgotPasswordEmailForm extends \yii\base\Model
{
    public $email;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email'], 'string', 'max' => 255],
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