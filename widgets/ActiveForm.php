<?php

namespace wmc\widgets;

use Yii;

class ActiveForm extends \yii\bootstrap\ActiveForm
{
    public $fieldClass = 'wmc\widgets\ActiveField';
}