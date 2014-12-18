<?php

namespace wmc\components\timecircles;

use yii\web\AssetBundle;

class TimeCirclesAsset extends AssetBundle
{
    public $sourcePath = '@wmc/components/timecircles/assets';
    public $css = [
        'css/TimeCircles.css'
    ];
    public $js = [
        'js/TimeCircles.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}