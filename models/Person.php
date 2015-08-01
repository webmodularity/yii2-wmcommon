<?php

namespace wmc\models;

use Yii;
use wmc\behaviors\RelatedModelBehavior;
use wmc\models\AddressPrimary;
use wmc\models\AddressShipping;
use wmc\models\AddressBilling;

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
 */
class Person extends \wmc\db\ActiveRecord
{
    const ADDRESS_PRIMARY = 1;
    const ADDRESS_SHIPPING = 2;
    const ADDRESS_BILLING = 3;

    static $prefix = ['Mr', 'Ms', 'Mrs', 'Miss', 'Mx', 'Dr', 'Prof', 'Hon', 'Rev', 'Fr'];

    static $suffix = ['Jr', 'Sr', 'II', 'III', 'IV', 'Esq', 'CPA', 'DC', 'DDS', 'VM', 'JD', 'MD', 'PhD',
        'USA', 'USA Ret', 'USAF', 'USAF Ret', 'USMC', 'USMC Ret', 'USN', 'USN Ret', 'USCG', 'USCG Ret'];

    protected $_deleteAddresses = [];

    public function behaviors() {
        return [
            'relatedModel' =>
                [
                    'class' => RelatedModelBehavior::className(),
                    'relations' => [
                        'primaryAddress' => [
                            'class' => AddressPrimary::className(),
                            'extraColumns' => ['address_type_id' => static::ADDRESS_PRIMARY]
                        ],
                        'phone' => [
                            'class' => Phone::className()
                        ]
                    ]
                ]
        ];
    }

    public static function find()
    {
        return parent::find()->with('primaryAddress', 'phone');
    }

    public function getIsEmpty() {
        return empty($this->first_name);
    }

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
    public function getOrganizations()
    {
        return $this->hasMany(OrganizationLocation::className(), ['id' => 'organization_id'])->viaTable('{{%organization_person}}', ['person_id' => 'id']);
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
    public function getPrimaryAddress()
    {
        return $this->hasOne(AddressPrimary::className(), ['id' => 'address_id'])->viaTable('{{%person_address}}', ['person_id' => 'id'], function($query) {
            $query->onCondition(['address_type_id' => static::ADDRESS_PRIMARY]);
        });
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShippingAddress()
    {
        return $this->hasOne(AddressShipping::className(), ['id' => 'address_id'])->viaTable('{{%person_address}}', ['person_id' => 'id'], function($query) {
            $query->onCondition(['address_type_id' => static::ADDRESS_SHIPPING]);
        });;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBillingAddress()
    {
        return $this->hasOne(AddressBilling::className(), ['id' => 'address_id'])->viaTable('{{%person_address}}', ['person_id' => 'id'], function($query) {
            $query->onCondition(['address_type_id' => static::ADDRESS_BILLING]);
        });;
    }

    /**
     * To be used in scenarios in which user has only one phone number associated (of any type)
     * @return \yii\db\ActiveQuery
     */
    public function getPhone() {
        return $this->hasOne(Phone::className(), ['id' => 'phone_id'])->viaTable('{{%person_phone}}', ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhones()
    {
        return $this->hasMany(Phone::className(), ['id' => 'phone_id'])->viaTable('{{%person_phone}}', ['person_id' => 'id']);
    }

}