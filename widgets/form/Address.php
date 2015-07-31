<?php

namespace wmc\widgets\form;

use Yii;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use wmc\models\AddressState;

class Address extends FormWidget
{
    const DEFAULT_STATE_PROMPT = 'Select a State...';

    protected $_attributeNames = [
        'street1' => 'street1',
        'street2' => 'street2',
        'city' => 'city',
        'state_id' => 'state_id',
        'zip' => 'zip'
    ];

    protected $_inputOptions = [
        'street1' => [],
        'street2' => [],
        'city' => [],
        'state_id' => [],
        'zip' => []
    ];
    protected $_fieldAliases = ['state' => 'state_id'];

    public $countryId;
    public $stateIdList = [];
    public $stateFullName = false;
    public $index = null;

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

        if (!empty($this->index)) {
            foreach ($this->_attributeNames as $key => $attributeName) {
                $this->_attributeNames[$key] = $attributeName . '[' . $this->index . ']';
            }
        }

        // State Prompt
        if (array_key_exists('prompt', $this->_inputOptions['state_id']) === false) {
            $this->_inputOptions['state_id']['prompt'] = static::DEFAULT_STATE_PROMPT;
        }
    }

    public function run() {
        return Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, $this->_attributeNames['street1'])->textInput($this->_inputOptions['street1'])->label($this->getLabel('street1')),
                ['class' => "col-lg-6"])
            . Html::tag('div',
                $this->form->field($this->model, $this->_attributeNames['street2'])->textInput($this->_inputOptions['street2'])->label($this->getLabel('street2')),
                ['class' => "col-lg-6"]),
            ['class' => "row"])

        . Html::tag('div',
            Html::tag('div',
                $this->form->field($this->model, $this->_attributeNames['city'])->textInput($this->_inputOptions['city'])->label($this->getLabel('city')),
                ['class' => "col-sm-5"])
            . Html::tag('div',
                $this->form->field($this->model, $this->_attributeNames['state_id'])->dropDownList($this->_stateValues,$this->_inputOptions['state_id'])->label($this->getLabel('state_id')),
                ['class' => "col-xs-6 col-sm-3"])
            . Html::tag('div',
                $this->form->field($this->model, $this->_attributeNames['zip'])->textInput($this->_inputOptions['zip'])->label($this->getLabel('zip')),
                ['class' => "col-xs-6 col-sm-4"]),
            ['class' => "row"]);
    }
}