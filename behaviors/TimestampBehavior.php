<?php

namespace wmc\behaviors;

use Yii;
use yii\db\Expression;

class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    protected function getValue($event) {
        if ($this->value instanceof Expression) {
            return $this->value;
        } else {
            return $this->value !== null ? call_user_func($this->value, $event) : new Expression('NOW()');
        }
    }
}
