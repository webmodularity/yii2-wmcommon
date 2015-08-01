<?php

namespace wmc\widgets\form;

use Yii;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

class FormWidget extends \yii\base\Widget
{
    public $form;
    public $model;

    public $enableLabel = true;

    protected $_fieldAliases = [];
    protected $_inputOptions = [];
    protected $_labels = [];
    protected $_readOnly = [];
    protected $_placeholderExcludeFields = [];

    public function setInputOptions($inputOptions) {
        if (is_array($inputOptions) && !empty($inputOptions)) {
            foreach ($inputOptions as $field => $options) {
                if (in_array($this->getFieldName($field), $this->getFieldNames())) {
                    if (isset($options['class'])) {
                        Html::addCssClass($this->_inputOptions[$this->getFieldName($field)], ArrayHelper::remove($options, 'class'));
                    }
                    if (isset($options['style'])) {
                        Html::addCssStyle($this->_inputOptions[$this->getFieldName($field)], ArrayHelper::remove($options, 'style'));
                    }
                    $this->_inputOptions[$this->getFieldName($field)] = ArrayHelper::merge(
                        $this->_inputOptions[$this->getFieldName($field)],
                        $options
                    );
                }
            }
        }
    }

    public function setReadOnly($readOnlys) {
        if (is_array($readOnlys) && !empty($readOnlys)) {
            foreach ($readOnlys as $readOnly) {
                if (in_array($this->getFieldName($readOnly), $this->getFieldNames())) {
                    $this->_readOnly[] = $this->getFieldName($readOnly);
                }
            }
        }
    }

    public function setLabels($labels) {
        if (is_array($labels) || !empty($labels)) {
            foreach ($labels as $field => $label) {
                if (in_array($this->getFieldName($field), $this->getFieldNames())) {
                    $this->_labels[$this->getFieldName($field)] = $label;
                }
            }
        }
    }

    public function init() {
        if (empty($this->form) || empty($this->model)) {
            throw new InvalidConfigException("FormWidget's require a valid form and model connection!");
        }

        if ($this->enableLabel === false) {
        // Placeholder Text
            foreach ($this->getFieldNames() as $field) {
                if (in_array($field, $this->_placeholderExcludeFields)) {
                    continue;
                }
                if (!isset($this->_inputOptions[$field]['placeholder'])) {
                    $this->_inputOptions[$field]['placeholder'] = $this->_labels[$field];
                }
            }
        }

        // Read Only
        foreach ($this->_readOnly as $readOnly) {
            $this->_inputOptions[$readOnly]['readonly'] = 'readonly';
        }
    }

    protected function getFieldNames() {
        return !empty($this->_inputOptions) ? array_keys($this->_inputOptions) : [];
    }

    protected function getFieldName($name) {
        return in_array($name, array_keys($this->_fieldAliases)) ? $this->_fieldAliases[$name] : $name;
    }

    protected function getLabel($field) {
        if ($this->enableLabel === false || !isset($this->_labels[$this->getFieldName($field)])) {
            return false;
        } else {
            return $this->_labels[$this->getFieldName($field)];
        }
    }

}