<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "person".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property integer $address_id
 *
 * @property BranchSupervisor[] $branchSupervisors
 * @property EquipmentPackage[] $equipmentPackages
 * @property EquipmentPerson[] $equipmentPeople
 * @property EquipmentRequest[] $equipmentRequests
 * @property License[] $licenses
 * @property User $user
 * @property Address $address
 * @property PersonDetail $personDetail
 * @property PersonPhone[] $personPhones
 * @property PersonThirdPartySystem[] $personThirdPartySystems
 * @property ThirdPartySystem[] $thirdPartySystems
 * @property RegionSupervisor[] $regionSupervisors
 */
class Person extends \wmc\models\ActiveRecord
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
            [['user_id', 'address_id'], 'integer'],
            [['email', 'first_name', 'last_name'], 'required'],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
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
            'user_id' => 'User ID',
            'email' => 'Email Address',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'address_id' => 'Address ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
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

}