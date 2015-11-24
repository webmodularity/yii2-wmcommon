<?php

namespace wmc\widgets\bootstrap;

use Yii;

class ConfirmationAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/bs-confirmation';
    public $js = ['bootstrap-confirmation.min.js'];
    public $depends = ['yii\web\JqueryAsset','wmc\web\JqueryRedirectAsset', 'yii\bootstrap\BootstrapAsset', 'yii\bootstrap\BootstrapPluginAsset'];
}