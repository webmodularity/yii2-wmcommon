<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%person}}".
 *
 * @property integer $id
 * @property string $prefix
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $suffix
 * @property string $nickname
 *
 * @property OrganizationPerson[] $organizationPeople
 * @property PersonAddress[] $personAddresses
 * @property PersonPhone[] $personPhones
 */
class Person extends \wmc\db\ActiveRecord
{
    static $prefix = ['Mr', 'Ms', 'Mrs', 'Miss', 'Mx', 'Dr', 'Prof', 'Hon', 'Rev', 'Fr'];

    static $suffix = ['Jr', 'Sr', 'II', 'III', 'IV', 'Esq', 'CPA', 'DC', 'DDS', 'VM', 'JD', 'MD', 'PhD',
        'USA', 'USA Ret', 'USAF', 'USAF Ret', 'USMC', 'USMC Ret', 'USN', 'USN Ret', 'USCG', 'USCG Ret'];

    protected $_deleteAddresses = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['first_name', 'middle_name', 'last_name', 'nickname'], 'string', 'max' => 255],
            [['prefix'], 'string', 'max' => 5],
            [['suffix'], 'string', 'max' => 10],
            [['first_name', 'last_name'], 'required']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'prefix' => 'Prefix',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'suffix' => 'Suffix',
            'nickname' => 'Nickname',
        ];
    }

    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPeople()
    {
        return $this->hasMany(OrganizationPerson::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizations()
    {
        return $this->hasMany(OrganizationLocation::className(), ['id' => 'organization_id'])->viaTable('{{%organization_person}}', ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonAddresses()
    {
        return $this->hasMany(PersonAddress::className(), ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Address::className(), ['id' => 'address_id'])->viaTable('{{%person_address}}', ['person_id' => 'id']);
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
    public function getPhones()
    {
        return $this->hasMany(Phone::className(), ['id' => 'phone_id'])->viaTable('{{%person_phone}}', ['person_id' => 'id']);
    }

    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            $this->_deleteAddresses = $this->addresses;
            return true;
        } else {
            return false;
        }
    }

    public function afterDelete() {
        foreach ($this->_deleteAddresses as $address) {
            try {
                $address->delete();
            } catch (\Exception $e) {
                // Ignore failed delete
            }
        }
    }
}