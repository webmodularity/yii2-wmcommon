<?php

namespace wmc\web;

use Yii;

class AlertManager extends \yii\base\Component
{
    const DEFAULT_FLASH_ID = 'alertManager';

    protected $_alerts = [];

    public function add($alert, $flashId = null) {
        $flashId = empty($flashId) ? static::DEFAULT_FLASH_ID : $flashId;
        if (!empty($alert)) {
            $flashArray = Yii::$app->session->getFlash($flashId, []);
            $flashArray[] = $alert;
            Yii::$app->session->setFlash($flashId, $flashArray);
        }
    }

    public function get($flashId = null, $glue = '') {
        $flashId = empty($flashId) ? static::DEFAULT_FLASH_ID : $flashId;
        $alerts = Yii::$app->session->getFlash($flashId, []);
        return implode($glue, $alerts);
    }
}