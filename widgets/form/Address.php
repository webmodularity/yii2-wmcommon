<?php

namespace wmc\widgets\form;

use Yii;
use wmc\helpers\Html;
use yii\base\InvalidConfigException;
use wmc\models\AddressState;

class Address extends FormWidget
{

    protected $_inputOptions = [
        'street1' => ['maxlength' => 255],
        'street2' => ['maxlength' => 255],
        'city' => ['maxlength' => 255],
        'state_id' => [],
        'zip' => ['maxlength' => 20]
    ];
    protected $_fieldAliases = ['state' => 'state_id'];

    public $countryId;
    public $stateIdList = [];
    public $stateFullName = false;
    public $statePrompt = 'Select a State...';

    protected $_labels = [
        'street1' => 'Street Address',
        'street2' => 'Address Line 2 (Optional)',
        'city' => 'City',
        'state_id' => 'State',
        'zip' => 'Zip'
    ];
    protected $_placeholderExcludeFields = ['state_id'];
    protected $_stateValues = [];

    public function init() {
        parent::init();
        if (!is_bool($this->stateFullName)) {
            throw new InvalidConfigException("AddressForm widget misconfiguration!");
        }
        $this->_stateValues = AddressState::getStateList($this->stateFullName, $this->countryId, $this->stateIdList);

        // State Prompt
        if (!isset($this->_inputOptions['state_id']['prompt']) && !is_null($this->statePrompt)) {
            $this->_inputOptions['state_id']['prompt'] = $this->statePrompt;
        }
    }

    public function run() {
        return Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, 'street1')->textInput($this->_inputOptions['street1'])->label($this->getLabel('street1')),
                ['class' => "col-lg-6"])
            . Html::tag('div',
                $this->form->field($this->model, 'street2')->textInput($this->_inputOptions['street2'])->label($this->getLabel('street2')),
                ['class' => "col-lg-6"]),
            ['class' => "row"])

        . Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, 'city')->textInput($this->_inputOptions['city'])->label($this->getLabel('city')),
                ['class' => "col-sm-5"])
            . Html::tag('div',
                $this->form->field($this->model, 'state_id')->dropDownList($this->_stateValues,$this->_inputOptions['state_id'])->label($this->getLabel('state_id')),
                ['class' => "col-xs-6 col-sm-3"])
            . Html::tag('div',
                $this->form->field($this->model, 'zip')->textInput($this->_inputOptions['zip'])->label($this->getLabel('zip')),
                ['class' => "col-xs-6 col-sm-4"]),
            ['class' => "row"]);
    }
}