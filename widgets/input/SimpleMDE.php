<?php

namespace wmc\widgets\input;

use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\helpers\Json;
use yii\web\JsExpression;

class SimpleMDE extends InputWidget
{
    public $options = [];
    public $clientOptions = [];
    public $uniqueId;

    public function init()
    {
        parent::init();
        if (!isset($this->options['id'])) {
            if ($this->hasModel()) {
                $this->options['id'] = Html::getInputId($this->model, $this->attribute);
            } else {
                $this->options['id'] = $this->getId();
            }
        }
        if (!$this->uniqueId) {
            if ($this->hasModel() && $this->model->page_id > 0) {
                $this->uniqueId = $this->model->page_id;
            } else {
                $this->uniqueId = "new";
            }
        }
        $this->clientOptions['element'] = new JsExpression('$("#'.$this->options['id'].'")[0]');
        if (!isset($this->clientOptions['autosave'])) {
            $this->clientOptions['autosave'] = [
                'enabled' => true,
                'delay' => 10000,
                'unique_id' => 'SMDE_' . $this->options['id'] . '_' . $this->uniqueId
            ];
        }
        $initValue = $this->hasModel() ? $this->model->{$this->attribute} : $this->value;
        $this->clientOptions['initialValue'] = $initValue;
        SimpleMDEAsset::register($this->getView());
        $this->registerScript();
    }
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }
    }
    public function registerScript()
    {
        $js = 'new SimpleMDE('.Json::encode($this->clientOptions).');';
        $this->getView()->registerJs($js);
    }
}