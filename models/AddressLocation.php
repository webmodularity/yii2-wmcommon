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
}