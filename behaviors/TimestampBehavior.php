<?php

namespace wmc\behaviors;

use Yii;

class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    protected function getValue($event) {
        if ($this->value instanceof Expression) {
            return $this->value;
        } else {
            return $this->value !== null ? call_user_func($this->value, $event) : Yii::$app->formatter->asMysqlDatetime();
        }
    }
}
