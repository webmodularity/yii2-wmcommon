<?php

namespace wmc\web;

use Yii;
use wmc\helpers\ArrayHelper;

class AlertManager extends \yii\base\Component
{
    const DEFAULT_FLASH_ID = 'alertManager';
    public $alertClass = 'wmc\widgets\Alert';
    private $_alertTypes = [
        'success' => [],
        'warning' => [],
        'info' => [],
        'danger' => []
    ];

    public function add($style, $message, $heading = null, $options = [], $flashId = self::DEFAULT_FLASH_ID) {
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
}