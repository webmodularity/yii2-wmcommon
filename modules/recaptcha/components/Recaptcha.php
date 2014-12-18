<?php

namespace wmc\modules\recaptcha\components;

use yii\base\InvalidConfigException;

class Recaptcha extends \yii\base\Component
{
    public $siteKey = null;
    public $secretKey = null;

    public function init() {
        if (!$this->siteKey || !$this->secretKey) {
            throw new InvalidConfigException("You must specify a siteKey & secretKey for recaptcha to work!");
        }
        parent::init();
    }
}