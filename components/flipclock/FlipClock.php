<?php

namespace wmc\components\flipclock;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;
use yii\helpers\Html;
use yii\helpers\Json;

class FlipClock extends Widget
{

    public function init() {
        // Register JS
        $js = new JsExpression("
        var clock = $('#flipclock').FlipClock(3600 * 24 * 3, {
		clockFace: 'DailyCounter',
		countdown: true
	    });
	    ");
        Yii::$app->view->registerJs($js);
        FlipClockAsset::register(Yii::$app->view);
    }

    public function run() {
        return Html::tag('div', '', ['id' => 'flipclock']);
    }
}