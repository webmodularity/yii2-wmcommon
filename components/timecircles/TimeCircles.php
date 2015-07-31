<?php

namespace wmc\components\timecircles;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;
use yii\helpers\Html;
use yii\helpers\Json;

class TimeCircles extends Widget
{
    public $date = null;
    public $_options = [];

    public function setOptions($options) {
        if (is_array($options)) {
            $this->_options = $options;
        }
    }

    public function getOptions() {
        return Json::encode($this->_options);
    }

    public function init() {
        if (!$this->date) {
            throw new InvalidConfigException("Date must be defined in TimeCircles widget!");
        }
        // Register JS
        $js = new JsExpression('$("#timecircles").TimeCircles('.$this->getOptions().');');
        Yii::$app->view->registerJs($js);
        TimeCirclesAsset::register(Yii::$app->view);
    }

    public function run() {
        return Html::tag('div', '', ['id' => 'timecircles', 'data-date' => $this->date]);
    }
}