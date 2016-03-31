<?php

namespace wmc\widgets\input;

use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\helpers\Json;
use yii\web\JsExpression;
use dosamigos\switchinput\SwitchBox;

class SwitchText extends InputWidget
{
    public function run() {
        if ($this->hasModel()) {

            //echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            //echo Html::textarea($this->name, $this->value, $this->options);
        }
    }
}