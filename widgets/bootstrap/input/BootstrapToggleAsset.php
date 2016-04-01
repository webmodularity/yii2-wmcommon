<?php

namespace wmc\widgets\bootstrap\input;

class ToggleAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/bootstrap-toggle';

    public $js = [
        'js/bootstrap-toggle.min.js'
    ];

    public $css = [
        'css/bootstrap-toggle.min.css'
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset'
    ];
}