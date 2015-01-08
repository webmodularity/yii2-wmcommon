<?php

namespace wmc\widgets;

use Yii;
use wmc\helpers\Html;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use wmc\models\AddressState;

class AddressForm extends Widget
{
    public $form = null;
    public $model = null;

    public function init() {
        if (!isset($this->form) || !isset($this->model)) {
            throw new InvalidConfigException("AddressForm widget requires a valid form and model connection!");
        }
    }

    public function run() {
        return Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, 'street1')->textInput([
                    'maxlength' => 255,
                    'placeholder' => 'Street Address'
                ]),
                ['class' => "col-lg-6"])
            . Html::tag('div',
                $this->form->field($this->model, 'street2')->textInput([
                    'maxlength' => 255,
                    'placeholder' => 'Address Line 2 (Optional)'
                ]),
                ['class' => "col-lg-6"]),
            ['class' => "row"])

        . Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, 'city')->textInput(['maxlength' => 255,  'placeholder' => 'City']),
                ['class' => "col-xs-12 col-md-5"])
            . Html::tag('div',
                $this->form->field($this->model, 'state')->dropDownList(AddressState::getStateList(),['prompt' => 'State']),
                ['class' => "col-xs-12 col-sm-4 col-md-3"])
            . Html::tag('div',
                $this->form->field($this->model, 'zip')->textInput(['maxlength' => 20, 'placeholder' => 'Zip']),
                ['class' => "col-xs-12 col-sm-8 col-md-4"]),
            ['class' => "row"]);
    }
}