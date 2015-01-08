<?php

namespace wmc\models;

use Yii;
use wmc\models\AddressCountry;
use wmc\models\AddressState;
use wmc\models\AddressLocation;
use wmc\models\AddressStreet;
use yii\db\IntegrityException;

/**
 * This is the model class for table "{{%address}}".
 *
 * @property integer $id
 * @property string $street1
 * @property string $street2
 * @property string $city
 * @property string $zip
 * @property string $state
 * @property string $country
 */
class Address extends \wmc\db\ActiveRecord
{
    private $_countryId;
    private $_stateId;
    private $_locationId;

    const DEFAULT_COUNTRY = 'US';

    const TYPE_PRIMARY = 1;
    const TYPE_SHIPPING = 2;
    const TYPE_BILLING = 3;

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
            [['street1', 'street2', 'city', 'state', 'zip'], 'trim'],
            [['street1', 'city', 'state', 'zip'], 'required'],
            [['street1', 'street2', 'city'], 'string', 'max' => 255],
            [['zip'], 'string', 'max' => 20],
            [['state', 'country'], 'string', 'max' => 2],
            [['country'], 'validateCountry', 'skipOnEmpty' => false],
            [['state'], 'validateState'],
            [['zip'], 'validateZip'],
            [['city'], 'validateCity'],
            [['street1', 'street2'], 'validateStreet']
            ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'street1' => 'Street1',
            'street2' => 'Street2',
            'city' => 'City',
            'zip' => 'Zip',
            'state' => 'State',
            'country' => 'Country'
        ];
    }

    public function setCountryId($countryIso) {
        $this->_countryId = AddressCountry::findIdFromIso($countryIso);
    }

    public function getCountryId() {
        if (is_null($this->_countryId)) {
            $countryIso = !empty($this->country) ? $this->country : static::DEFAULT_COUNTRY;
            $this->setCountryId($countryIso);
        }
        return $this->_countryId;
    }

    public function setStateId($stateIso, $countryId) {
        $this->_stateId = AddressState::findIdFromIso($stateIso, $countryId);
    }

    public function getStateId() {
        if (is_null($this->_stateId)) {
            $this->setStateId($this->state, $this->countryId);
        }
        return $this->_stateId;
    }

    public function setLocationId($city, $stateId, $zip) {
        $location = AddressLocation::findOne(
            [
                'city' => $city,
                'state_id' => $stateId,
                'zip' => $zip
            ]
        );
        if (!is_null($location)) {
            $this->_locationId = $location->id;
        }
    }

    public function getLocationId() {
        if (is_null($this->_locationId)) {
            $this->setLocationId($this->city, $this->getStateId(), $this->zip);
        }
        return $this->_locationId;
    }

    public function getOneLine() {
        $street = !empty($this->street2) ? $this->street1 . ',' . $this->street2 : $this->street1;
        return $street . ',' . $this->city . ',' . $this->state . ',' . $this->zip;
    }

    public function validateCountry($attribute, $params) {
        $this->$attribute = strtoupper($this->$attribute);
        if (!$this->getCountryId()) {
            $this->addError($attribute, 'Unrecognized country!');
        }
    }

    public function validateState($attribute, $params) {
        $this->$attribute = strtoupper($this->$attribute);
        if (!$this->getStateId()) {
            $this->addError($attribute, 'Unrecognized state!');
        }
    }

    public function validateZip($attribute, $params) {
        if (empty($this->_countryId)) {
            $this->addError($attribute, 'Unable to validate zip - country not set!');
        } else {
            $normalizedZip = AddressLocation::normalizeZip($this->$attribute, $this->_countryId);
            if ($normalizedZip === false) {
                $this->addError($attribute, 'Invalid zip code!');
            } else {
                $this->$attribute = $normalizedZip;
            }
        }
    }

    public function validateCity($attribute, $params) {
        $this->$attribute = AddressLocation::normalizeCity($this->$attribute, $this->_countryId);
    }

    public function validateStreet($attribute, $params) {
        $this->$attribute = AddressStreet::normalizeStreet($this->$attribute, $this->_countryId);
    }

    /**
     * @inheritdoc
     */
    protected function insertInternal($attributes = null) {

        if (!$this->beforeSave(true)) {
            return false;
        }

        $values = $this->getDirtyAttributes($attributes);

        // Find Location or add one if doesn't exist
        $locationId = $this->getLocationId();
        if (is_null($locationId)) {
            if (!$this->addLocation()) {
                Yii::info('Failed to save address location.');
                return false;
            } else {
                $locationId = $this->getLocationId();
            }
        }

        // Make sure we don't already have an address record
        $street = AddressStreet::findOne(
            [
                'street1' => $values['street1'],
                'street2' => $values['street2'],
                'location_id' => $locationId
            ]
        );
        if (!is_null($street)) {
            // Record already exists
            Yii::info('Address already exists.');
            return false;
        }
        $street = new AddressStreet();
        $street->street1 = $values['street1'];
        $street->street2 = $values['street2'];
        $street->location_id = $locationId;
        if (!$street->save()) {
            Yii::info('Failed to save street address.');
            return false;
        }

        // Set ID to AddressStreet ID value
        $this->setAttribute('id', $street->id);

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);
        return true;
    }

    protected function updateInternal($attributes = null)
    {

        if (!$this->beforeSave(false)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);

        if (empty($values)) {
            $this->afterSave(false, $values);
            return 0;
        }

        $location = null;
        $streetValues = [];
        foreach ($values as $key => $val) {
            if (in_array($key, ['city', 'state', 'zip'])) {
                // Find Location or add one if doesn't exist
                $locationId = $this->getLocationId();
                if (is_null($locationId)) {
                    if (!$this->addLocation()) {
                        return 0;
                    } else {
                        $locationId = $this->getLocationId();
                    }
                }

                $streetValues['location_id'] = $locationId;
                break;
            }
        }

        foreach (['street1', 'street2'] as $st) {
            if (isset($values[$st])) {
                $streetValues[$st] = $values[$st];
            }
        }

        // Check if we have a matching record
        $existingStreet = AddressStreet::findOne([
            'street1' => $this->street1,
            'street2' => $this->street2,
            'location_id' => $this->getLocationId()
        ]);
        if (is_null($existingStreet)) {
            $rows = AddressStreet::updateAll($streetValues, ['id' => $this->id]);
        } else {
            $thisStreet = AddressStreet::findOne($this->id);
            $rows = static::mergeRedundantRecord($thisStreet, $existingStreet);
            $this->setOldAttribute('id', $this->id);
            $this->id = $existingStreet->id;
        }

        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $changedAttributes[$name] = $this->getOldAttribute($name) ? $this->getOldAttribute($name) : null;
            $this->setOldAttribute($name, $value);
        }
        $this->afterSave(false, $changedAttributes);
        return $rows;
    }

    protected function deleteInternal()
    {
        if (!$this->beforeDelete()) {
            return false;
        }

        $street = AddressStreet::findOne($this->id);
        if (!is_null($street)) {
            try {
                $result = $street->delete();
            } catch (\Exception $e) {
                throw new IntegrityException("Address is in use and cannot be deleted.");
            }
        } else {
            throw new StaleObjectException('The object being deleted is outdated.');
        }

        $this->setOldAttributes(null);
        $this->afterDelete();
        return $result;
    }

    protected function addLocation() {
        $location = new AddressLocation();
        $location->city = $this->city;
        $location->state_id = $this->getStateId();
        $location->zip = $this->zip;
        return $location->save();
    }

    protected function getAddressStreet() {

    }

}