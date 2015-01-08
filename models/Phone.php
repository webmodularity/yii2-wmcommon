<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%phone}}".
 *
 * @property integer $id
 * @property string $area_code
 * @property string $number
 * @property string $extension
 *
 * @property OrganizationPhone[] $organizationPhones
 * @property PersonPhone[] $personPhones
 */
class Phone extends \wmc\db\ActiveRecord
{
    const TYPE_DIRECT = 1;
    const TYPE_MOBILE = 2;
    const TYPE_OFFICE = 3;
    const TYPE_FAX = 4;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%phone}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['area_code', 'number'], 'required'],
            [['area_code'], 'string', 'max' => 3],
            [['number'], 'string', 'max' => 7],
            [['extension'], 'string', 'max' => 5],
            [['area_code', 'number', 'extension'], 'match', 'pattern' => '/[0-9]/'],
            [['area_code', 'number', 'extension'], 'unique', 'targetAttribute' => ['area_code', 'number', 'extension'], 'message' => 'This phone number is already in use.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'area_code' => 'Area Code',
            'number' => 'Number',
            'extension' => 'Extension',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPhones()
    {
        return $this->hasMany(OrganizationPhone::className(), ['phone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonPhones()
    {
        return $this->hasMany(PersonPhone::className(), ['phone_id' => 'id']);
    }
}