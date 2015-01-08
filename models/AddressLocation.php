<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%address_location}}".
 *
 * @property integer $id
 * @property string $city
 * @property integer $state_id
 * @property string $zip
 *
 * @property AddressState $state
 * @property AddressStreet[] $addressStreets
 */
class AddressLocation extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%address_location}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['city', 'state_id', 'zip'], 'required'],
            [['state_id'], 'integer'],
            [['city'], 'string', 'max' => 255],
            [['zip'], 'string', 'max' => 20],
            [['city', 'state_id', 'zip'], 'unique', 'targetAttribute' => ['city', 'state_id', 'zip'], 'message' => 'The address location is already in use.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city' => 'City',
            'state_id' => 'State ID',
            'zip' => 'Zip',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getState()
    {
        return $this->hasOne(AddressState::className(), ['id' => 'state_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddressStreets()
    {
        return $this->hasMany(AddressStreet::className(), ['location_id' => 'id']);
    }

    public static function normalizeZip($zip, $countryId = 1) {
        if ($countryId == 1) {
            if (!preg_match('/^[0-9]{5}(?:-[0-9]{4})?$/', $zip)) {
                if (preg_match('/^([0-9]{5})[ \+_]{0,1}([0-9]{4})$/', $zip, $match)) {
                    return $match[1] . '-' . $match[2];
                } else {
                    return false;
                }
            } else {
                return $zip;
            }
        } else if ($countryId == 2) {
            $zipUpper = strtoupper($zip);
            if (!preg_match('/^[ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ] \d[ABCEGHJKLMNPRSTVWXYZ]\d$/', $zipUpper)) {
                if (preg_match(
                    '/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/',
                    $zipUpper,
                    $match
                )) {
                    return $match[1] . ' ' . $match[2];
                } else {
                    return false;
                }
            } else {
                return $zipUpper;
            }
        } else {
            // Implement international postal code regex
        }
    }

    public static function normalizeCity($city, $countryId = 1) {
        if ($exception = static::cityIsException($city)) {
            return $exception;
        }
        // Split city on spaces
        $cityParts = explode(' ', $city);
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
        return implode(' ', $normalizedCityParts);
    }

    public static function normalizeCityPart($part, $isFirstWord) {
        if ($isFirstWord === true || !in_array($part, static::cityNoCapWords())) {
            return ucfirst($part);
        } else {
            return $part;
        }
    }

    public static function cityNoCapWords() {
        return ['and', 'the', 'of', 'by', 'at', 'for', 'in', 'on', 'to', 'up'];
    }

    public static function cityIsException($city) {
        $exceptions = [
            "coeur d'alene" => "Coeur d’Alene"
        ];
        $cityLower = strtolower($city);
        return isset($exceptions[$cityLower]) ? $exceptions[$cityLower] : false;
    }
}