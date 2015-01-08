<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%organization_person}}".
 *
 * @property integer $organization_id
 * @property integer $person_id
 * @property integer $contact_type_id
 *
 * @property OrganizationLocation $organization
 * @property Person $person
 * @property OrganizationPersonContactType $contactType
 */
class OrganizationPerson extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%organization_person}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['organization_id', 'person_id', 'contact_type_id'], 'required'],
            [['organization_id', 'person_id', 'contact_type_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'organization_id' => 'Organization ID',
            'person_id' => 'Person ID',
            'contact_type_id' => 'Contact Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganization()
    {
        return $this->hasOne(OrganizationLocation::className(), ['id' => 'organization_id']);
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
    public function getContactType()
    {
        return $this->hasOne(OrganizationPersonContactType::className(), ['id' => 'contact_type_id']);
    }
}