<?php

namespace wmc\widgets\bootstrap\input;

use yii\helpers\Html;
use yii\bootstrap\InputWidget;
use yii\helpers\Json;
use yii\web\JsExpression;

class TextToggle extends InputWidget
{
    public $options = ['class' => 'form-control'];

    public function run() {
        if ($this->hasModel()) {
            //return Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            //echo Html::textarea($this->name, $this->value, $this->options);
        }
        return Html::beginTag('div', ['class' => 'input-group'])
            . Html::beginTag('span', ['class' => 'input-group-btn'])
                // Button
            . Html::endTag('span')
            // Input
            . Html::endTag('div');
    }
}