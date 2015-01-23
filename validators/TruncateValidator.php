<?php

namespace wmc\validators;

use yii\helpers\StringHelper;

class TruncateValidator extends \yii\validators\Validator
{
    public $length = 255;
    public $suffix = '';

    public function validateAttribute($model, $attribute) {
        $model->$attribute = StringHelper::truncate($model->$attribute, $this->length, $this->suffix);
    }
}