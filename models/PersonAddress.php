<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%person_address}}".
 *
 * @property integer $person_id
 * @property integer $address_id
 * @property integer $address_type_id
 *
 * @property Person $person
 * @property AddressStreet $address
 */
class PersonAddress extends \wmc\db\ActiveRecord
{
    public static function find() {
        return parent::find()->joinWith('address');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person_address}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['person_id', 'address_id', 'address_type_id'], 'required'],
            [['person_id', 'address_id', 'address_type_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'person_id' => 'Person ID',
            'address_id' => 'Address ID',
            'address_type_id' => 'Address Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPerson()
    {
        return $this->hasOne(Person::className(), ['id' => 'person_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(Address::className(), ['id' => 'address_id']);
    }
}