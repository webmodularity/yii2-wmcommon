<?php

namespace wmc\widgets\bootstrap\input;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\InputWidget;
use wmc\widgets\bootstrap\input\BootstrapToggle;

class ToggleEmpty extends InputWidget
{
    public $options = ['class' => 'form-control'];

    protected $_toggleOptions = ['on' => 'Yes', 'off' => 'No'];
    protected $_placeholder;

    private $_toggleButtonAttributeName;

    /**
     * Set toggleOptions of BootstrapToggle widget
     * @param $options array Options to be passed to BootstrapToggle widget - id key is ignored as it is auto generated
     * @see wmc\widgets\bootstrap\input\BootstrapToggle
     */

    public function setToggleOptions($options) {
        if (is_array($options)) {
            if (isset($options['id'])) {
                unset($options['id']);
            }
            $this->_toggleOptions = ArrayHelper::merge($this->_toggleOptions, $options);
        }
    }

    /**
     * By default sets on->Yes and off->No
     * @return array toggleOptions to be passed to BootstrapToggle widget
     */

    public function getToggleOptions() {
        return $this->_toggleOptions;
    }

    /**
     * Used to specify placeholder option of text input field. If not set will default to label for this attribute.
     * @param $placeholderText string Text to use as placeholder of text input field
     */

    public function setPlaceholder($placeholderText) {
        if (!empty($placeholderText) && is_string($placeholderText)) {
            $this->_placeholder = $placeholderText;
        }
    }

    /**
     * @return string Placeholder text for input field
     */

    public function getPlaceholder() {
        return $this->_placeholder;
    }

    public function init() {
        if (!$this->hasModel()) {
            throw new InvalidConfigException("ToggleEmpty widget only supports inputs associated with models!");
        }

        // Toggle Button
        $this->_toggleButtonAttributeName = $this->model->generateToggleButtonAttributeName($this->attribute);
        if (!$this->model->{$this->_toggleButtonAttributeName}) {
            $this->options['readonly'] = true;
        }
        // Placeholder
        if (empty($this->getPlaceholder())) {
            $this->placeholder = Html::encode($this->model->getAttributeLabel($this->attribute));
        }
        $this->options['placeholder'] = $this->getPlaceholder();
        // Old Val
        $this->options['data']['old-val'] = $this->value;

        parent::init();
    }

    public function run() {
        $this->registerClientScript();
        return Html::beginTag('div', ['class' => 'input-group'])
            . Html::beginTag('span', ['class' => 'input-group-btn'])
                . BootstrapToggle::widget([
                    'model' => $this->model,
                    'attribute' => $this->_toggleButtonAttributeName,
                    'toggleOptions' => $this->getToggleOptions()
                ])
            . Html::endTag('span')
            . Html::activeTextInput($this->model, $this->attribute, $this->options)
            . Html::endTag('div');
    }

    public function registerClientScript() {
        $attributeId = Html::getInputId($this->model, $this->attribute);
        $toggleAttributeId = Html::getInputId($this->model, $this->_toggleButtonAttributeName);
        $js = "$('#".$toggleAttributeId."').change(function() {
            if ($(this).prop('checked') == true) {
                var oldVal = $('#".$attributeId."').data('old-val');
                $('#".$attributeId."').prop('readonly', false).val(oldVal);
            } else {
                var textInputVal = $('#".$attributeId."').val();
                $('#".$attributeId."').prop('readonly', true).val('').data('old-val', textInputVal);

            }
        })";

        $this->getView()->registerJs($js);
    }
}