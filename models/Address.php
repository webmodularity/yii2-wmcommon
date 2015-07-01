<?php

namespace wmc\models;

use Yii;
use yii\db\IntegrityException;

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
    const LOCATION_GC_FREQ = 10;

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
            [['street1', 'street2', 'city', 'state_iso', 'state_name', 'zip', 'country_iso', 'country_name'], 'trim'],
            [['street1', 'city', 'state_id', 'zip'], 'required', 'on' => 'required'],
            [['city', 'state_id', 'zip'], 'required', 'when' => function($model) {
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
            [['street1', 'street2', 'city'], 'string', 'max' => 255],
            [['zip'], 'string', 'max' => 20],
            [['country_name'], 'string', 'max' => 50],
            [['state_name'], 'string', 'max' => 75],
            [['state_iso', 'country_iso'], 'string', 'max' => 2],
            [['location_id', 'state_id', 'country_id'], 'integer'],
            [['location_id', 'state_id', 'country_id'], 'filter', 'filter' => 'intval'],
            [['country_id'], 'validateCountry', 'skipOnEmpty' => false],
            [['state_id'], 'validateState', 'skipOnEmpty' => false],
            [['location_id'], 'validateLocation', 'skipOnEmpty' => false],
            [['street1', 'street2'], 'normalizeStreet', 'skipOnEmpty' => false]
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

    public function getOneLine() {
        $street = !empty($this->street2) ? $this->street1 . ',' . $this->street2 : $this->street1;
        return $street . ',' . $this->city . ',' . $this->state_iso . ',' . $this->zip;
    }

    public function validateCountry($attribute, $params) {
        $countryId = null;
        if (empty($this->country_id)) {
            if (empty($this->country_name) && empty($this->country_iso)) {
                // Lets pull country info from $this->state_id
                if (!empty($this->state_id)) {
                    $state = AddressState::findOne($this->state_id);
                     if (!is_null($state)) {
                         $countryId = $state->country_id;
                         $this->setAttribute('country_id', $countryId);
                     }
                }
            } else {
                $countryId = !empty($this->country_iso)
                    ? AddressCountry::findIdFromIso($this->country_iso)
                    : AddressCountry::findIdFromName($this->country_name);
                if (!is_null($countryId)) {
                    $this->setAttribute('country_id', $countryId);
                }
            }
        } else {
            // Verify country_id points to a valid country
            $country = AddressCountry::findOne($this->country_id);
            if (!is_null($country) && $country->id == $this->country_id) {
                $countryId = $country->id;
            }
        }

        if (is_null($countryId)) {
            $this->addError($attribute, 'Unrecognized Country!');
        }
    }

    public function validateState($attribute, $params) {
        $stateId = null;
        if (!empty($this->country_id)) {
            if (empty($this->state_id)) {
                $stateId = !empty($this->state_iso)
                    ? AddressState::findIdFromIso($this->state_iso, $this->country_id)
                    : AddressState::findIdFromName($this->state_name, $this->country_id);
                if (!is_null($stateId)) {
                    $this->setAttribute('state_id', $stateId);
                }
            } else {
                // Verify state_id points to a valid state (in specified country)
                $state = AddressState::findOne(['country_id' => $this->country_id, 'id' => $this->state_id]);
                if (!is_null($state) && $state->id == $this->state_id) {
                    $stateId = $state->id;
                }
            }
        }

        if (is_null($stateId)) {
            $this->addError($attribute, 'Unrecognized State!');
        }
    }

    public function validateLocation($attribute, $params) {
        $locationId = null;
        $normalizedZip = AddressLocation::normalizeZip($this->zip, $this->country_id);
        if ($normalizedZip !== false) {
            $this->zip = $normalizedZip;
            $this->city = AddressLocation::normalizeCity($this->city, $this->country_id);
            // Find Location or add one if doesn't exist
            $location = AddressLocation::findOne([
                'city' => $this->city,
                'zip' => $this->zip,
                'state_id' => $this->state_id
            ]);
            if (is_null($location)) {
                $location = new AddressLocation();
                $location->city = $this->city;
                $location->state_id = $this->state_id;
                $location->zip = $this->zip;
                if ($location->save()) {
                    $locationId = $location->id;
                    $this->setAttribute('location_id', $locationId);
                }
            } else {
                $locationId = $location->id;
                $this->setAttribute('location_id', $locationId);
            }
        }

        if ($normalizedZip === false) {
            $this->addError('zip', 'Invalid zip code!');
        } else if (is_null($locationId)) {
            $this->addError($attribute, 'Invalid city/state/zip combo!');
        }
    }

    public function normalizeStreet($attribute, $params) {
        $this->$attribute = AddressStreet::normalizeStreet($this->$attribute, $this->country_id);
    }

    /**
     * @inheritdoc
     */
    protected function insertInternal($attributes = null) {
        if (!$this->beforeSave(true)) {
            return false;
        }

        $values = $this->getDirtyAttributes($attributes);

        // Make sure we don't already have an address record
        $street = AddressStreet::findOne([
                'street1' => $this->street1,
                'street2' => $this->street2,
                'location_id' => $this->location_id
        ]);
        if (!is_null($street)) {
            // Record already exists
            Yii::info('Address already exists.');
            return false;
        }

        $street = new AddressStreet();
        $street->street1 = $this->street1;
        $street->street2 = $this->street2;
        $street->location_id = $this->location_id;
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

    protected function updateInternal($attributes = null) {
        if (!$this->beforeSave(false)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        $streetValues = [];

        if (empty($values)) {
            $this->afterSave(false, $values);
            return 0;
        }

        // Disallow country change
        if (in_array('country_id', array_keys($values))) {
            return false;
        }

        // Ensure state change stays within same country
        if (in_array('state_id', array_keys($values))) {
            $stateCountry = AddressState::findOne($values['state_id']);
            if (is_null($stateCountry) || $stateCountry->country_id != $this->country_id) {
                return false;
            }
        }

        // Handle location_id changes
        if (in_array('location_id', array_keys($values))) {
            $streetValues['location_id'] = $values['location_id'];
        }

        // Catch street1/street2 changes
        foreach ($values as $key => $val) {
            if (in_array($key, ['street1', 'street2'])) {
                $streetValues[$key] = $val;
            }
        }

        // Check if we have an existing record
        $existingAddress = AddressStreet::findOne([
            'street1' => $this->street1,
            'street2' => $this->street2,
            'location_id' => $this->location_id
        ]);

        if (is_null($existingAddress)) {
            $rows = AddressStreet::updateAll($streetValues, ['id' => $this->id]);
        } else {
            $thisStreet = AddressStreet::findOne($this->id);
            $rows = static::mergeRedundantRecord($thisStreet, $existingAddress);
            $this->setOldAttribute('id', $this->id);
            $this->id = $existingAddress->id;
        }

        $this->doLocationGc();
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

        $this->doLocationGc();
        $this->setOldAttributes(null);
        $this->afterDelete();
        return $result;
    }

    protected function doLocationGc() {
        if (mt_rand(1,100) <= static::LOCATION_GC_FREQ) {
            $db = static::getDb();
            $db->createCommand("DELETE address_location FROM address_location
                    LEFT JOIN address_street ON address_street.location_id = address_location.id
                    WHERE address_street.id IS NULL"
            )
                ->execute();
        }
    }

}