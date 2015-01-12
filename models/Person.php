<?php

namespace wmc\models;

use Yii;

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
    public $email_confirm;
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
            [['email_confirm'], 'email', 'on' => 'register'],
            [['email_confirm'], 'required', 'on' => 'register'],
            [['email_confirm'], 'compare', 'compareAttribute' => 'email', 'message' => 'Emails do not match.',  'on' => 'register'],
        ];
    }

    public function afterSave($insert, $changedAttributes) {
        if ($insert === true) {
            // Check for personName relation or create new blank record
            $personName = isset($this->personName) ? $this->personName : null;
            if (is_null($personName)) {
                $personName = new PersonName();
            }
            $personName->person_id = $this->id;
            if ($personName->save()) {
                $this->populateRelation('personName', $personName);
            }
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        return parent::find()->joinWith('personName');
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

    public static function findByEmail($email) {
        return static::findOne(['email' => $email]);
    }
}