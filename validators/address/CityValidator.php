<?php

namespace wmc\validators\address;

class CityValidator extends \yii\validators\Validator
{
    static $exceptions = [
        "coeur d'alene" => "Coeur d’Alene"
    ];
    static $cityNoCapWords = ['and', 'the', 'of', 'by', 'at', 'for', 'in', 'on', 'to', 'up'];

    public function validateAttribute($model, $attribute) {
        if (isset(static::$exceptions[strtolower($model->$attribute)])) {
            $model->$attribute = static::$exceptions[strtolower($model->$attribute)];
        } else {
            // Split city on spaces
            $cityParts = explode(' ', $model->$attribute);
            $isFirstWord = true;
            $normalizedCityParts = [];
            foreach ($cityParts as $part) {
                $part = strtolower($part);
                // Handle - and '
                if (strpos($part, "-") !== false) {
                    $partTemp = [];
                    $subPart = explode("-", $part);
                    foreach ($subPart as $sub) {
                        $partTemp[] = static::normalizeCityPart($sub, $isFirstWord);
                    }
                    $normalizedCityParts[] = implode("-", $partTemp);
                } else if (strpos($part, "'") !== false) {
                    $partTemp = [];
                    $subPart = explode("'", $part);
                    foreach ($subPart as $sub) {
                        $partTemp[] = static::normalizeCityPart($sub, $isFirstWord);
                    }
                    $normalizedCityParts[] = implode("'", $partTemp);
                } else {
                    $normalizedCityParts[] = static::normalizeCityPart($part, $isFirstWord);
                }
                if ($isFirstWord === true) {
                    $isFirstWord = false;
                }
            }
            $model->$attribute = implode(' ', $normalizedCityParts);
        }
    }

    public static function normalizeCityPart($part, $isFirstWord) {
        if ($isFirstWord === true || !in_array($part, static::cityNoCapWords)) {
            return ucfirst($part);
        } else {
            return $part;
        }
    }
}