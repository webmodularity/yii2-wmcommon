<?php

namespace wmc\widgets;

use Yii;
use wmc\helpers\Html;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use wmc\models\AddressState;

class AddressForm extends Widget
{
    public $form;
    public $model;
    public $countryId;

    public $stateIdList = [];
    public $stateFullName = false;
    public $statePrompt = 'State';

    public $readOnly = [];

    public $usePlaceholders = false;
    public $placeholderText = [
        'street1' => 'Street Address',
        'street2' => 'Address Line 2 (Optional)',
        'city' => 'City',
        'zip' => 'Zip'
    ];

    public $inputOptions = [
        'street1' => ['maxlength' => 255],
        'street2' => ['maxlength' => 255],
        'city' => ['maxlength' => 255],
        'state' => [],
        'zip' => ['maxlength' => 20]

    ];

    private $_fields = ['street1', 'street2', 'city', 'state', 'zip'];
    private $_stateValues = [];

    public function init() {
        if (empty($this->form) || empty($this->model) || !is_bool($this->stateFullName)) {
            throw new InvalidConfigException("AddressForm widget requires a valid form and model connection!");
        }
        $this->_stateValues = AddressState::getStateList($this->stateFullName, $this->countryId, $this->stateIdList);

        // State Prompt
        if (!isset($this->inputOptions['state']['prompt']) && !is_null($this->statePrompt)) {
            $this->inputOptions['state']['prompt'] = $this->statePrompt;
        }

        // Placeholder Text
        if ($this->usePlaceholders === true) {
            foreach ($this->_fields as $field) {
                if ($field == 'state') {
                    continue;
                }
                if (!isset($this->inputOptions[$field]['placeholder'])) {
                    $this->inputOptions[$field]['placeholder'] = $this->placeholderText[$field];
                }
            }
        }

        // Read Only
        foreach ($this->readOnly as $ro) {
            if (in_array($ro, $this->_fields)) {
                $this->inputOptions[$ro]['readonly'] = 'readonly';
            }
        }
    }

    public function run() {
        return Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, 'street1')->textInput($this->inputOptions['street1']),
                ['class' => "col-lg-6"])
            . Html::tag('div',
                $this->form->field($this->model, 'street2')->textInput($this->inputOptions['street2']),
                ['class' => "col-lg-6"]),
            ['class' => "row"])

        . Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, 'city')->textInput($this->inputOptions['city']),
                ['class' => "col-xs-12 col-md-5"])
            . Html::tag('div',
                $this->form->field($this->model, 'state_id')->dropDownList($this->_stateValues,$this->inputOptions['state']),
                ['class' => "col-xs-12 col-sm-4 col-md-3"])
            . Html::tag('div',
                $this->form->field($this->model, 'zip')->textInput($this->inputOptions['zip']),
                ['class' => "col-xs-12 col-sm-8 col-md-4"]),
            ['class' => "row"]);
    }
}