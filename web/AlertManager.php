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

    /** Depreciated - use addAlert instead */
    /*
    public function Dadd($style, $message, $heading = null, $options = [], $flashId = self::DEFAULT_FLASH_ID) {
        if ($style == 'error') {
            $style = 'danger';
        }
        $class = $this->alertClass;
        $settings = compact('class', 'style', 'message', 'heading');
        $config = ArrayHelper::merge($settings, $options);
        $alertObject = Yii::createObject($config);
        if (!is_null($alertObject)) {
            $alertHtml = $alertObject->run();
            if ($alertHtml) {
                $flashArray = Yii::$app->session->hasFlash($flashId)
                    ? Yii::$app->session->getFlash($flashId)
                    : $this->_alertTypes;
                $flashArray[$style][] = $alertHtml;
                Yii::$app->session->setFlash($flashId, $flashArray);
            }
        }
    }

    public function render($flashId = self::DEFAULT_FLASH_ID) {
        $alertHtml = '';
        $alerts = Yii::$app->session->getFlash($flashId, []);
        foreach ($alerts as $alertStyle => $alertList) {
            foreach ($alertList as $alert) {
                $alertHtml .= $alert;
            }
        }
        return $alertHtml;
    }
    */
}