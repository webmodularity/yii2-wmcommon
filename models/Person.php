<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%person}}".
 *
 * @property integer $id
 * @property string $email
 *
 * @property OrganizationPerson[] $organizationPeople
 * @property OrganizationLocation[] $organizations
 * @property PersonAddress[] $personAddresses
 * @property AddressStreet[] $addresses
 * @property PersonName $personName
 * @property PersonPhone[] $personPhones
 */
class Person extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email'], 'trim'],
            [['email'], 'required'],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPeople()
    {
        return $this->hasMany(OrganizationPerson::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizations()
    {
        return $this->hasMany(OrganizationLocation::className(), ['id' => 'organization_id'])->viaTable('{{%organization_person}}', ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonAddresses()
    {
        return $this->hasMany(PersonAddress::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(AddressStreet::className(), ['id' => 'address_id'])->viaTable('{{%person_address}}', ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonName()
    {
        return $this->hasOne(PersonName::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonPhones()
    {
        return $this->hasMany(PersonPhone::className(), ['person_id' => 'id']);
    }
}