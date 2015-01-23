<?php

namespace wmc\models;

use Yii;
use yii\base\Exception;

/**
 * This is the model class for table "{{%person}}".
 *
 * @property integer $id
 * @property string $email
 *
 * @property OrganizationPerson[] $organizationPeople
 * @property OrganizationLocation[] $organizations
 * @property PersonAddress[] $personAddresses
 * @property AddressStreet[] $addresses
 * @property PersonName $personName
 * @property PersonPhone[] $personPhones
 */
class Person extends \wmc\db\ActiveRecord
{
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
            [['email'], 'trim'],
            [['email'], 'required'],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
        ];
    }

    /**
     * UPDATE NEEDS WORK
     * @param bool $insert
     * @param array $changedAttributes
     */

    public function afterSave($insert, $changedAttributes) {
        if ($insert === true) {
            $this->personName->person_id = $this->id;
            try {
                $this->personName->save();
            } catch (Exception $e) {

            }
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeValidate() {
        if ($this->personName->validate() === false) {
            return false;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public static function find() {
        return parent::find()->joinWith('personName');
    }

    public function init() {
        if ($this->isNewRecord) {
            $this->populateRelation('personName', new PersonName());
        }
        parent::init();
    }

    public function load($data, $formName = null) {
        // Load PersonName
        $this->personName->load($data, $formName);

        return parent::load($data, $formName);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'email_confirm' => 'Confirm Email'
        ];
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
        return $this->hasMany(AddressStreet::className(), ['id' => 'address_id'])->viaTable('{{%person_address}}', ['person_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonName()
    {
        return $this->hasOne(PersonName::className(), ['person_id' => 'id']);
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

    public static function findByEmail($email) {
        return static::findOne(['email' => $email]);
    }
}