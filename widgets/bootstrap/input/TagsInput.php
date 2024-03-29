<?php

namespace wmc\widgets\bootstrap\input;

use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\helpers\Json;

class TagsInput extends InputWidget
{
    public $options = ['class' => 'form-control'];
    public $clientOptions = [];
    public $clientEvents = [];
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
        TagsInputAsset::register($this->getView());
        $this->registerScript();
        $this->registerEvent();
    }
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeInput('text', $this->model, $this->attribute, $this->options);
        } else {
            echo Html::input('text', $this->name, $this->value, $this->options);
        }
    }
    public function registerScript()
    {
        $this->getView()->registerCss(".bootstrap-tagsinput { width: 100%; }");
        $clientOptions = empty($this->clientOptions) ? '' : Json::encode($this->clientOptions);
        $js = "jQuery('#{$this->options["id"]}').tagsinput({$clientOptions});";
        $this->getView()->registerJs($js);
    }
    public function registerEvent()
    {
        if (!empty($this->clientEvents)) {
            $js = [];
            foreach ($this->clientEvents as $event => $handle) {
                $js[] = "jQuery('#{$this->options["id"]}').on('$event',$handle);";
            }
            $this->getView()->registerJs(implode(PHP_EOL, $js));
        }
    }
}