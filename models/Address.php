<?php

namespace wmc\models;

use Yii;
use wmc\behaviors\FindOrInsertBehavior;

/**
 * This is the model class for table "{{%address}}".
 *
 * @property integer $id
 * @property string $street1
 * @property string $street2
 * @property integer $location_id
 * @property string $city
 * @property string $zip
 * @property integer $state_id
 * @property string $state_name
 * @property string $state_iso
 * @property integer $country_id
 * @property string $country_name
 * @property string $country_iso
 */
class Address extends \wmc\db\ActiveRecord
{
    public $country_id = 1;

    public function behaviors() {
        return [
            'findOrInsert' =>
                [
                    'class' => FindOrInsertBehavior::className()
                ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%address}}';
    }

    public static function primaryKey() {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {

        return [
            [['street1', 'street2', 'city', 'zip'], 'trim'],
            [['country_id'], 'required'],
            [['state_id', 'country_id'], 'integer', 'min' => 1],
            [['state_id', 'country_id'], 'filter', 'filter' => 'intval'],
            [['street1', 'street2'], 'string', 'max' => 255],
            [['street1', 'street2'], '\wmc\validators\address\StreetValidator'],
            [['state_id'], '\wmc\validators\address\StateValidator', 'isEmpty' => function ($value) {return empty($value);}],
            [['zip'], 'string', 'max' => 20],
            [['zip'], '\wmc\validators\address\ZipValidator'],
            [['city'], 'string', 'max' => 255],
            [['city'], '\wmc\validators\address\CityValidator'],
            [['city', 'state_id', 'zip'], 'required', 'except' => 'required',
                'when' => function($model) {
                return !empty($model->street1);
            }, 'whenClient' => "function (attribute, value) {
                var streetEmpty = true;
                $(\"#\"+attribute.id+\"\").closest('fieldset').find('input').each(function(index) {
                var eleId = $(this).attr('id');
                if (eleId.substr(eleId.length - 7) == 'street1') {
                    if ($(this).val()) {
                        streetEmpty = false;
                    }
                    return false;
                }
                });
                return !streetEmpty;}"
            ],
            [['street1', 'city', 'state_id', 'zip'], 'required', 'on' => 'required']
            ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'street1' => 'Street Address',
            'street2' => 'Address Line 2',
            'location_id' => 'Location',
            'city' => 'City',
            'zip' => 'Zip',
            'state_id' => 'State',
            'state_iso' => 'State ISO',
            'state_name' => 'State Name',
            'country_id' => 'Country',
            'country_iso' => 'Country ISO',
            'country_name' => 'Country Name'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeople() {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])->viaTable('{{%person_address}}', ['address_id' => 'id']);
    }

    public function getOneLine() {
        $street = !empty($this->street2) ? $this->street1 . ',' . $this->street2 : $this->street1;
        return $street . ', ' . $this->city . ', ' . $this->state_iso . ', ' . $this->zip;
    }

    public function save($runValidation = true, $attributeNames = null) {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }
        $this->setLocationId();
        $attributes = ['street1', 'street2', 'location_id'];
        if ($this->getIsNewRecord()) {
            $addressStreet = new AddressStreet;
            $addressStreet->setAttributes($this->getAttributes($attributes), false);
            if ($addressStreet->insert(false)) {
                $this->id = $addressStreet->id;
                $this->refresh();
                return true;
            } else {
                return false;
            }
        } else {
            $addressStreet = $this->getAddressStreetModel();
            if (is_null($addressStreet)) {
                return false;
            }
            $addressStreet->setAttributes($this->getAttributes($attributes), false);
            if ($addressStreet->update(false) !== false) {
                $this->refresh();
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete() {
        $addressStreet = $this->getAddressStreetModel();
        if (!is_null($addressStreet)) {
            return $addressStreet->delete();
        } else {
            return 0;
        }
    }

    protected function setLocationId() {
        if (!$this->isEmpty) {
            $location = new AddressLocation;
            $location->setAttributes($this->getAttributes(['city', 'state_id', 'zip']));
            if ($location->findOrInsert()) {
                $this->location_id = $location->id;
            }

        }
    }

    protected function getAddressStreetModel() {
        return AddressStreet::findOne($this->getPrimaryKey(true));
    }

    /* relatedModel */

    public function getIsEmpty() {
        return empty($this->street1);
    }

    public static function relatedModelAttributes() {
        return ['street1', 'street2', 'city', 'state_id', 'zip'];
    }

}