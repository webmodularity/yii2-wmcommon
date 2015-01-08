<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%organization}}".
 *
 * @property integer $id
 * @property string $name
 *
 * @property OrganizationLocation[] $organizationLocations
 */
class Organization extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%organization}}';
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
    public function getOrganizationLocations()
    {
        return $this->hasMany(OrganizationLocation::className(), ['organization_id' => 'id']);
    }
}