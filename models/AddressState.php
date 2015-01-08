<?php

namespace wmc\models;

use Yii;
use wmc\helpers\ArrayHelper;

/**
 * This is the model class for table "common.address_state".
 *
 * @property integer $id
 * @property string $name
 * @property string $iso
 * @property integer $country_id
 *
 * @property AddressCountry $country
 */
class AddressState extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.address_state';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'name', 'country_id'], 'required'],
            [['id', 'country_id'], 'integer'],
            [['name'], 'string', 'max' => 75],
            [['iso'], 'string', 'max' => 2]
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
            'country_id' => 'Country',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(AddressCountry::className(), ['id' => 'country_id']);
    }

    public static function findIdFromIso($iso, $countryId) {
        if (is_string($iso) && strlen($iso) == 2 && is_int($countryId) && $countryId > 0) {
            $state = static::findOne(['country_id' => $countryId, 'iso' => $iso]);
            if (!is_null($state)) {
                return $state->id;
            } else {
                return null;
            }
        }
        return null;
    }

    public static function getStateList($countryId = 1, $iso = true) {
        $display = $iso === true ? 'iso' : 'name';
        return ArrayHelper::map(
            static::find()
                ->select(['iso', $display])
                ->andWhere(['country_id' => $countryId])
                ->orderBy('id ASC')
                ->asArray()
                ->all(),
            'iso', $display);
    }
}