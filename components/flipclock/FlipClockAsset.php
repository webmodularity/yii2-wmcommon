<?php

namespace wmc\components\flipclock;

use yii\web\AssetBundle;

class FlipClockAsset extends AssetBundle
{
    public $sourcePath = '@wmc/components/flipclock/assets';
    public $css = [
        'css/flipclock.css'
    ];
    public $js = [
        'js/flipclock.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}