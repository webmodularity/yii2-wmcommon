<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%organization_person_contact_type}}".
 *
 * @property integer $id
 * @property string $name
 *
 * @property OrganizationPerson[] $organizationPeople
 */
class OrganizationPersonContactType extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%organization_person_contact_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'unique']
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
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPeople()
    {
        return $this->hasMany(OrganizationPerson::className(), ['contact_type_id' => 'id']);
    }
}