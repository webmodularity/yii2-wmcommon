<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.address_country".
 *
 * @property integer $id
 * @property string $name
 * @property string $iso
 * @property string $iso3
 * @property string $fips
 * @property string $continent
 * @property string $currency_code
 * @property string $tld
 * @property integer $phone_code
 * @property string $postal_regex
 *
 * @property AddressState[] $addressStates
 */
class AddressCountry extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.address_country';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'iso', 'iso3', 'fips', 'continent', 'currency_code', 'tld', 'phone_code', 'postal_regex'], 'required'],
            [['phone_code'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['iso', 'fips', 'continent'], 'string', 'max' => 2],
            [['iso3', 'currency_code', 'tld'], 'string', 'max' => 3],
            [['postal_regex'], 'string', 'max' => 200],
            [['iso'], 'unique'],
            [['name'], 'unique'],
            [['iso3'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'iso' => 'ISO',
            'iso3' => 'ISO3',
            'fips' => 'FIPS',
            'continent' => 'Continent',
            'currency_code' => 'Currency Code',
            'tld' => 'TLD',
            'phone_code' => 'Phone Code',
            'postal_regex' => 'Postal Regex',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddressStates()
    {
        return $this->hasMany(AddressState::className(), ['country_id' => 'id']);
    }

    public static function findIdFromIso($iso) {
        if (is_string($iso) && strlen($iso) == 2) {
            if (strtolower($iso) == 'us') {
                return 1;
            } else if (strtolower($iso) == 'ca') {
                return 2;
            } else {
                $country = static::findOne(['iso' => $iso]);
                if (!is_null($country)) {
                    return $country->id;
                } else {
                    return null;
                }
            }
        }
        return null;
    }
}