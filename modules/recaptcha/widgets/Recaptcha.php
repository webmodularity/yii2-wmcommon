<?php

namespace wmc\modules\recaptcha\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

class Recaptcha extends \Zelenin\yii\widgets\Recaptcha\widgets\Recaptcha
{

    public function init() {
        if (!Yii::$app->has('recaptcha')) {
            throw new InvalidConfigException("The recaptcha widget is not properly configured!");
        }
        $siteKey = Yii::$app->recaptcha->siteKey;
        $this->clientOptions = ArrayHelper::merge($this->clientOptions, ['data-sitekey' => $siteKey]);
        parent::init();
    }

}