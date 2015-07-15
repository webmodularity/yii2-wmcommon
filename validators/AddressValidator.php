<?php

namespace wmc\validators;

class AddressZipValidator extends \yii\validators\Validator
{
    public function validateAttribute($model, $attribute) {
        if (!in_array($model->$attribute, ['USA', 'Web'])) {
            $this->addError($model, $attribute, 'The country must be either "USA" or "Web".');
        }
    }
}