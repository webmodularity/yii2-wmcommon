<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%organization_phone}}".
 *
 * @property integer $organization_id
 * @property integer $phone_id
 * @property integer $phone_type_id
 *
 * @property OrganizationLocation $organization
 * @property Phone $phone
 */
class OrganizationPhone extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%organization_phone}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['organization_id', 'phone_id', 'phone_type_id'], 'required'],
            [['organization_id', 'phone_id', 'phone_type_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'organization_id' => 'Organization ID',
            'phone_id' => 'Phone ID',
            'phone_type_id' => 'Phone Type ID',
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
    public function getPhone()
    {
        return $this->hasOne(Phone::className(), ['id' => 'phone_id']);
    }
}