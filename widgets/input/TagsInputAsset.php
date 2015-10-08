<?php

namespace wmc\widgets\input;

use Yii;

class TagsInputAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/bootstrap-tagsinput';
    public $js = ['dist/bootstrap-tagsinput.js'];
    public $css = ['dist/bootstrap-tagsinput.css'];
    public $depends = ['yii\web\JqueryAsset'];
}