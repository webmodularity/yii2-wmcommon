<?php

namespace wmc\validators;

use yii\helpers\Html;

class OneRequiredValidator extends \yii\validators\Validator
{
    public $skipOnEmpty = false;

    public function validateAttribute($model, $attribute) {
        $emptyCount = 0;
        foreach ($this->attributes as $emptyAttribute) {
            if ($this->isEmpty($model->$emptyAttribute)) {
                $emptyCount++;
            }
        }
        if ((count($this->attributes) - $emptyCount != 1)) {
            foreach ($this->attributes as $att) {
                $messages = $this->buildMessages($model, $att);
                $messageKey = $emptyCount == 0 ? 'tooMany' : 'notEnough';
                $this->addError($model, $att, $messages[$messageKey]);
            }
        }
    }

    public function clientValidateAttribute($model, $attribute, $view) {
        $options = ['attributes' => [], 'messages' => []];
        foreach ($this->attributes as $att) {
            $options['inputIds'][] = Html::getInputId($model, $att);
        }
        $options['messages'] = $this->buildMessages($model, $attribute);
        ValidationAsset::register($view);

        return 'wmc.validation.onerequired(value, messages, ' . json_encode($options) . ');';
    }


    protected function buildMessages($model, $attribute) {
        $isAre = count($this->attributes) == 2 ? ' is' : ' are';
        return [
            'notEnough' => $model->getAttributeLabel($attribute) . ' is required when '
                . $this->buildOtherFields($model, $attribute, 'and') . $isAre . ' NOT set.',
            'tooMany' => $model->getAttributeLabel($attribute) . ' must NOT be set if '
                . $this->buildOtherFields($model, $attribute, 'or') . $isAre . ' set.'
        ];
    }

    protected function buildOtherFields($model, $attribute, $andOr = 'or') {
        $labels = [];
        $attributes = $this->attributes;
        if (($key = array_search($attribute, $attributes)) !== false) {
            unset($attributes[$key]);
        }
        foreach ($attributes as $att) {
            $labels[] = $model->getAttributeLabel($att);
        }
        if (count($labels) == 1) {
            return array_pop($labels);
        } else {
            $lastLabel = array_pop($labels);
            return implode(', ', $labels)
            . ' ' . $andOr . ' '
            . $lastLabel;
        }
    }
}