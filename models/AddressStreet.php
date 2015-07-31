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
 */
class AddressStreet extends \wmc\db\ActiveRecord
{
    public $city;
    public $state_id;
    public $zip;

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
            [['street2'], 'safe']
        ];
    }

    public function afterSave($insert, $changedAttributes) {
        if ($insert === false && in_array('location_id', array_keys($changedAttributes))) {
            $this->doLocationGc();
        }
        parent::afterSave($insert, $changedAttributes);
    }


    /**
     * TODO: convert this to ActiveQuery
     */

    protected function doLocationGc() {
        $db = static::getDb();
        $db->createCommand("DELETE address_location FROM address_location
                    LEFT JOIN address_street ON address_street.location_id = address_location.id
                    WHERE address_street.id IS NULL")->execute();
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
}