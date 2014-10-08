<?php

namespace wmc\validators;

use yii\web\AssetBundle;

class ValidationAsset extends AssetBundle
{
    public $sourcePath = '@wmc/assets';
    public $js = [
        'wmc.validation.js',
    ];
    public $depends = [
        'yii\validators\ValidationAsset',
        'wmc\web\WmcAsset'
    ];
}