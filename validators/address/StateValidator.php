<?php

namespace wmc\validators\address;

use wmc\models\AddressState;

class StateValidator extends \yii\validators\Validator
{
    public function validateAttribute($model, $attribute) {
        $countryId = isset($model->country_id) ? $model->country_id : 1;
        $state = AddressState::find()->where(['country_id' => $countryId, 'id' => $model->$attribute])->count();
        if ($state != 1) {
            $this->addError($model, $attribute, 'Unrecognized State!');
        }
    }

}