<?php

namespace wmc\web;

use Yii;

class JqueryRedirectAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/jquery.redirect';
    public $js = ['jquery.redirect.js'];
    public $depends = ['yii\web\JqueryAsset'];
}