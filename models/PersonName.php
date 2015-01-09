<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%person_name}}".
 *
 * @property integer $person_id
 * @property integer $prefix_id
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property integer $suffix_id
 * @property string $nickname
 *
 * @property Person $person
 * @property PersonNamePrefix $prefix
 * @property PersonNameSuffix $suffix
 */
class PersonName extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person_name}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['person_id'], 'required', 'except' => ['register']],
            [['person_id', 'prefix_id', 'suffix_id'], 'integer'],
            [['first_name', 'last_name'], 'required', 'on' => 'register'],
            [['first_name', 'middle_name', 'last_name', 'nickname'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'person_id' => 'Person ID',
            'prefix_id' => 'Prefix ID',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'suffix_id' => 'Suffix ID',
            'nickname' => 'Nickname',
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
    public function getPrefix()
    {
        return $this->hasOne(PersonNamePrefix::className(), ['id' => 'prefix_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuffix()
    {
        return $this->hasOne(PersonNameSuffix::className(), ['id' => 'suffix_id']);
    }
}