<?php

namespace wmc\validators\address;

class ZipValidator extends \yii\validators\Validator
{
    static $regEx = [
        1 => "^([0-9]{5})[- ]?([0-9]{4})?$",
        2 => "^(?!.*[DFIOQU])([A-VXY][0-9][A-Z]) ?([0-9][A-Z][0-9])$"
    ];

    public function validateAttribute($model, $attribute) {
        $countryId = !isset($model->country_id) || empty($model->country_id) ? 1 : $model->country_id;
        $regEx = isset(static::$regEx[$countryId]) ? static::$regEx[$countryId] : null;
        if ($countryId  == 1) {
            // USA
            if (!preg_match("/".$regEx."/", $model->$attribute, $match)) {
                $this->addError($model, $attribute, 'Invalid US Zip!');
            } else {
                if (!empty($match[2])) {
                    // Normalize Zip
                    $model->$attribute = $match[1] . '-' . $match[2];
                }
            }
        } else if ($countryId == 2) {
            // Canada
            if (!preg_match('/".$regEx."/', $model->$attribute, $match)) {
                $this->addError($model, $attribute, 'Invalid Canadian Postal Code!');
            } else {
                // Normalize Zip
                $model->$attribute = $match[1] . ' ' . $match[2];
            }
        } else {
            if (!is_null($regEx)) {
                if (!preg_match('/".$regEx."/', $model->$attribute)) {
                    $this->addError($model, $attribute, 'Invalid Postal Code!');
                }
            }
        }
    }

    /**
     * Does not currently support changing of country after page load
     */

    public function clientValidateAttribute($model, $attribute, $view) {
        $countryId = !isset($model->country_id) || empty($model->country_id) ? 1 : $model->country_id;
        $regEx = isset(static::$regEx[$countryId]) ? static::$regEx[$countryId] : null;
        if (!is_null($regEx)) {
            if ($countryId == 1) {
                $message = 'Invalid US Zip!';
            } else {
                $message = 'Invalid Postal Code';
            }
            return <<<JS
var zipRegExp = new RegExp('$regEx');
if (value && !zipRegExp.test(value)) {
    messages.push('$message');
}
JS;
        } else {
            return '';
        }
    }
}