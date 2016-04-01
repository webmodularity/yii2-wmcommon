<?php

namespace wmc\widgets\bootstrap\input;

use yii\helpers\Html;
use yii\bootstrap\InputWidget;
use yii\helpers\Json;
use yii\web\JsExpression;
use lo\widgets\Toggle;

class TextToggle extends InputWidget
{
    public $options = ['class' => 'form-control', 'placeholder' => 'Menu Title...'];

    public function run() {
        if ($this->hasModel()) {
            //return Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            //echo Html::textarea($this->name, $this->value, $this->options);
        }
        return Html::beginTag('div', ['class' => 'input-group'])
            . Html::beginTag('span', ['class' => 'input-group-btn'])
                . Toggle::widget(['name' => 'text-toggle-btn', 'checked' => true, 'options' => [
                    'data-on' => 'Yes',
                    'data-off' => 'No',
                    'data-onstyle' => 'primary',
                    'data-offstyle' => 'default'
                ]])
            . Html::endTag('span')
            . Html::activeTextInput($this->model, $this->attribute, $this->options)
            . Html::endTag('div');
    }
}