<?php

namespace wmc\widgets\bootstrap\input;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\InputWidget;

class BootstrapToggle extends InputWidget
{
    static $toggleOptionKeys = [
        'on', 'off',
        'onstyle', 'offstyle',
        'size', 'style',
        'width', 'height'
    ];

    protected $_toggleOptions = [];

    /**
     * Set the options for Bootstrap Toggle. Do not use a leading data- when specifying options.
     * INCORRECT: ['data-on' => 'Yes']
     * CORRECT: ['on' => 'Yes']
     * @param $options array Config array keyed by valid toggleOption [[static::$_toggleOptionKeys]]
     * @see http://www.bootstraptoggle.com/
     */

    public function setToggleOptions($options) {
        foreach ($options as $key => $option) {
            if (in_array($key, static::$toggleOptionKeys) && is_string($option)) {
                $this->_toggleOptions[$key] = $option;
            }
        }
    }

    /**
     * Returns raw toggleOptions
     * @return array toggle options intended to be used via API (no data- , use [[$this->getToggleDataOptions()]] instead)
     */

    public function getToggleOptions() {
        return $this->_toggleOptions;
    }

    /**
     * Returns toggleOptions with leading data- to be used as data attributes of checkbox
     * @return array data- options used to convert checkbox into toggle
     */

    public function getToggleDataOptions() {
        $toggleOptions = ['data-toggle' => 'toggle'];
        foreach ($this->_toggleOptions as $key => $option) {
            $toggleOptions['data-' . $key] = $option;
        }
        return $toggleOptions;
    }

    public function init() {
        parent::init();
        if (!isset($this->options['id'])) {
            if ($this->hasModel()) {
                $this->options['id'] = Html::getInputId($this->model, $this->attribute);
            } else {
                $this->options['id'] = $this->getId();
            }
        }
        BootstrapToggleAsset::register($this->getView());
        Html::addCssClass($this->options, 'form-control');
    }

    public function run() {
        $options = ArrayHelper::merge($this->options, $this->getToggleDataOptions());
        if ($this->hasModel()) {
            echo Html::activeCheckbox($this->model, $this->attribute, $options);
        } else {
            echo Html::checkbox($this->name, $this->value, $options);
        }
    }
}