<?php

namespace wmc\widgets\form;

use Yii;
use wmc\helpers\Html;
use yii\base\InvalidConfigException;

class PersonName extends FormWidget
{

    public function init() {
        parent::init();
    }

    public function run() {
        return Html::tag('div', Html::tag('div',
            $this->form->field($this->model, 'first_name')->textInput(['placeholder' => 'First Name'])->label(false),
            ['class' => 'col-sm-6'])
        . Html::tag('div',
            $this->form->field($this->model, 'last_name')->textInput(['placeholder' => 'Last Name'])->label(false),
            ['class' => 'col-sm-6']),
            ['class' => 'row']);
    }

}