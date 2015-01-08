<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%organization_location}}".
 *
 * @property integer $id
 * @property integer $organization_id
 * @property string $name
 * @property integer $address_id
 *
 * @property Address $address
 * @property Organization $organization
 * @property OrganizationPerson[] $organizationPeople
 * @property Person[] $people
 * @property OrganizationPhone[] $organizationPhones
 */
class OrganizationLocation extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%organization_location}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['organization_id', 'address_id'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['organization_id', 'name'], 'unique', 'targetAttribute' => ['organization_id', 'name'], 'message' => 'The combination of Organization ID and Name has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_id' => 'Organization ID',
            'name' => 'Name',
            'address_id' => 'Address ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(Address::className(), ['id' => 'address_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganization()
    {
        return $this->hasOne(Organization::className(), ['id' => 'organization_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPeople()
    {
        return $this->hasMany(OrganizationPerson::className(), ['organization_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeople()
    {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])->viaTable('{{%organization_person}}', ['organization_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPhones()
    {
        return $this->hasMany(OrganizationPhone::className(), ['organization_id' => 'id']);
    }
}