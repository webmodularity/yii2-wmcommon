<?php

namespace wmc\web;

use yii\web\AssetBundle;

class WmcAsset extends AssetBundle
{
    public $sourcePath = '@wmc/assets';
    public $js = [
        'wmc.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}