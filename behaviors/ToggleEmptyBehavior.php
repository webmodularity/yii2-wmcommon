<?php

namespace wmc\behaviors;

use Yii;
use yii\base\Behavior;
use yii\validators\Validator;
use yii\base\InvalidConfigException;
use wmc\db\ActiveRecord;

class ToggleEmptyBehavior extends Behavior
{
    const TOGGLE_POSTFIX = '_toggle_button';

    protected $_attributes = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind'
        ];
    }

    public function setAttributes($attributes) {
        if (!empty($attributes)) {
            if (is_string($attributes)) {
                $this->_attributes[] = $attributes;
            } else if (is_array($attributes)) {
                $this->_attributes = $attributes;
            }
        }
    }

    public function getAttributes() {
        return $this->_attributes;
    }

    public function attach($owner) {
        parent::attach($owner);

        foreach ($this->_attributes as $attribute) {
            if (!$this->owner->hasProperty($this->generateToggleButtonAttributeName($attribute))) {
                throw new InvalidConfigException("ToggleEmptyBehavior has been misconfigured! Please add a property to
                base model (".$this->owner->className().") with a name of ".$this->generateToggleButtonAttributeName($attribute)."
                to hold the value of the toggle checkbox.");
            }
        }

        $owner->validators->append(Validator::createValidator('required', $owner, $this->_attributes, [
            'when' => function($model, $attribute) {
                $toggleButtonAttribute = $model->generateToggleButtonAttributeName($attribute);
                return $model->$toggleButtonAttribute == 1;
            },
            'whenClient' => "function (attribute, value) {
                var toggleButtonAttribute = attribute.id + '".static::TOGGLE_POSTFIX."';
                return $('#'+toggleButtonAttribute).prop('checked') == true;
            }"
        ]));
        $owner->validators->append(Validator::createValidator('default', $owner, $this->_attributes, ['value' => null]));
    }

    public function generateToggleButtonAttributeName($attribute) {
        return $attribute . static::TOGGLE_POSTFIX;
    }

    public function afterFind($event) {
        foreach ($this->_attributes as $attribute) {
            $toggleButtonAttributeName = $this->generateToggleButtonAttributeName($attribute);
            if (empty($this->owner->$attribute)) {
                $this->owner->$toggleButtonAttributeName = 0;
            } else {
                $this->owner->$toggleButtonAttributeName = 1;
            }
        }
    }
}