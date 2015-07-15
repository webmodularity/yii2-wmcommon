<?php

namespace wmc\validators\address;

class StreetValidator extends \yii\validators\Validator
{

    static $wordCaps = [
        'n','e','s','w',
        'ne','nw','se','sw'
    ];

    public function validateAttribute($model, $attribute) {
        $streetParts = explode(' ', $model->$attribute);
        $normalizedStreet = [];
        foreach ($streetParts as $part) {
            if (in_array(strtolower($part), static::$wordCaps)) {
                $normalizedStreet[] = strtoupper($part);
            } else {
                $normalizedStreet[] = $part;
            }
        }
        $model->$attribute = rtrim(implode(' ', $normalizedStreet), '.');
    }

}