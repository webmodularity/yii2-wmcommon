<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "person".
 *
 * @property integer $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property integer $address_id
 *
 * @property Address $address
 * @property PersonDetail $personDetail
 * @property PersonPhone[] $personPhones
 * @property User $user
 */
class Person extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'person';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'first_name', 'last_name'], 'trim'],
            [['email', 'first_name', 'last_name'], 'required'],
            [['email'], 'string', 'max' => 100],
            ['email', 'email'],
            [['address_id'], 'integer'],
            [['first_name', 'last_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'address_id' => 'Address',
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
    public function getPersonDetail()
    {
        return $this->hasOne(PersonDetail::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonPhones()
    {
        return $this->hasMany(PersonPhone::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['person_id' => 'id']);
    }

    public function getFullName() {
        return $this->first_name . "&nbsp;" . $this->last_name;
    }
}