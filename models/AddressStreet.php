<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%address_street}}".
 *
 * @property integer $id
 * @property string $street1
 * @property string $street2
 * @property integer $location_id
 *
 * @property AddressLocation $location
 * @property OrganizationLocation[] $organizationLocations
 * @property Person[] $people
 */
class AddressStreet extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%address_street}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['street1', 'location_id'], 'required'],
            [['location_id'], 'integer'],
            [['street1', 'street2'], 'string', 'max' => 255],
            [['street1', 'street2', 'location_id'], 'unique', 'targetAttribute' => ['street1', 'street2', 'location_id'], 'message' => 'The address is already in use.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'street1' => 'Street',
            'street2' => 'Street2',
            'location_id' => 'Location',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLocation()
    {
        return $this->hasOne(AddressLocation::className(), ['id' => 'location_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationLocations()
    {
        return $this->hasMany(OrganizationLocation::className(), ['address_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeople()
    {
        return $this->hasMany(Person::className(), ['address_id' => 'id']);
    }
}