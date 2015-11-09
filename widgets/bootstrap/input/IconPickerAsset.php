<?php

namespace wmc\widgets\bootstrap\input;

use Yii;

class IconPickerAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/bootstrap-iconpicker';
    public $js = ['dist/bootstrap-iconpicker.min.js'];
    public $css = ['dist/bootstrap-iconpicker.min.css'];
    public $depends = ['yii\web\JqueryAsset'];
}