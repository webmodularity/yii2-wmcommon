<?php

namespace wmc\modules\recaptcha\validators;

use yii\base\InvalidConfigException;
use Yii;

class RecaptchaValidator extends \Zelenin\yii\widgets\Recaptcha\validators\RecaptchaValidator
{
    public function init() {
        if (!Yii::$app->has('recaptcha')) {
            throw new InvalidConfigException("A Recaptcha component is required for the RecaptchaValidator!");
        }
        $this->secret = Yii::$app->recaptcha->secretKey;
        parent::init();
    }
}