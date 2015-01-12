<?php

namespace wmc\models;

use Yii;
use wmc\models\Phone;

/**
 * This is the model class for table "{{%person_phone}}".
 *
 * @property integer $person_id
 * @property integer $phone_id
 * @property integer $phone_type_id
 *
 * @property Phone $phone
 * @property Person $person
 */
class PersonPhone extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person_phone}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['person_id', 'phone_id', 'phone_type_id'], 'required'],
            [['person_id', 'phone_id', 'phone_type_id'], 'integer'],
            ['phone_type_id', function ($attribute, $params) {
                if (!in_array($this->$attribute, array_keys(Phone::getTypeList()))) {
                    $this->addError($attribute, 'Unrecognized phone type.');
                }
            }, 'skipOnEmpty' => false]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'person_id' => 'Person ID',
            'phone_id' => 'Phone ID',
            'phone_type_id' => 'Phone Type ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhone()
    {
        return $this->hasOne(Phone::className(), ['id' => 'phone_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPerson()
    {
        return $this->hasOne(Person::className(), ['id' => 'person_id']);
    }
}