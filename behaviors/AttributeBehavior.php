<?php

namespace wmc\behaviors;

class AttributeBehavior extends \yii\base\Behavior
{
    protected $_attributes = [];

    public function setAttributes($attributes) {
        if (!empty($attributes) && is_string($attributes)) {
            $this->_attributes[] = $attributes;
        } else if (is_array($attributes)) {
            $this->_attributes = $attributes;
        }
    }

    public function getAttributes() {
        return $this->_attributes;
    }
}