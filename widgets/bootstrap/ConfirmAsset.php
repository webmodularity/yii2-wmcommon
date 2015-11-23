<?php

namespace wmc\widgets\bootstrap;

use Yii;

class ConfirmAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/bs-confirmation';
    public $js = ['bootstrap-confirmation.min.js'];
    public $depends = ['yii\web\JqueryAsset'];
}