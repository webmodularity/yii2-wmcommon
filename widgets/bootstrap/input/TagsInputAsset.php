<?php

namespace wmc\widgets\bootstrap\input;

use Yii;

class TagsInputAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/bootstrap-tagsinput';
    public $js = ['dist/bootstrap-tagsinput.min.js'];
    public $css = ['dist/bootstrap-tagsinput.css'];
    public $depends = ['yii\web\JqueryAsset', 'yii\bootstrap\BootstrapPluginAsset'];
}