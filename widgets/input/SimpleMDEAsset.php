<?php

namespace wmc\widgets\input;

use Yii;

class SimpleMDEAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/simplemde';
    public $js = ['dist/simplemde.min.js'];
    public $css = ['dist/simplemde.min.css'];
    public $depends = ['yii\web\JqueryAsset'];
}