<?php

namespace wmc\behaviors;

use Yii;
use wmc\behaviors\AttributeBehavior;
use yii\validators\Validator;
use yii\base\InvalidConfigException;
use wmc\db\ActiveRecord;

class ToggleEmptyBehavior extends AttributeBehavior
{
    const TOGGLE_POSTFIX = '_toggle_button';

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'syncToggleButton',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'syncToggleButton',
        ];
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

    public function syncToggleButton($event) {
        foreach ($this->_attributes as $attribute) {
            $this->setToggle($attribute);
        }
    }

    protected function setToggle($attribute) {
        $toggleButtonAttributeName = $this->generateToggleButtonAttributeName($attribute);
        if (empty($this->owner->$attribute)) {
            $this->owner->$toggleButtonAttributeName = 0;
        } else {
            $this->owner->$toggleButtonAttributeName = 1;
        }
    }
}