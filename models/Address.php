<?php

namespace wmc\models;

use Yii;
use yii\base\InvalidCallException;
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
    const TYPE_PRIMARY = 1;
    const TYPE_SHIPPING = 2;
    const TYPE_BILLING = 3;

    public $country_id = 1;

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

    public function saveAddress($relationName, $relatedModel, $addressTypeId = Address::TYPE_PRIMARY) {
        // Always validate to ensure field normalization
        if (!$this->validate()) {
            return false;
        }

        if (!$this->isEmpty && !empty($this->getDirtyAttributes(['street1', 'street2', 'city', 'state_id', 'zip']))) {
            // Address was changed, create new address , unlink current address,
            // attempt to delete current address (for garbage collection), then link new address
            $addressClass = get_called_class();
            $newAddress = new $addressClass([
                'country_id' => $this->country_id,
                'street1' => $this->street1,
                'street2' => $this->street2,
                'city' => $this->city,
                'state_id' => $this->state_id,
                'zip' => $this->zip
            ]);
            if (!$newAddress->save(false)) {
                Yii::error("Failed to save new address.");
                return false;
            }
            try {
                $this->unlink($relationName, $relatedModel, true);
            } catch (InvalidCallException $e) {
                Yii::error("Failed to unlink address!");
                return false;
            }
            $this->delete();
            try {
                $newAddress->link($relationName, $relatedModel, ['address_type_id' => $addressTypeId]);
            } catch (InvalidCallException $e) {
                Yii::error("Failed to link new address!");
                return false;
            }
            return true;
        } else if ($this->isEmpty && !$this->isNewRecord) {
            // Address was set but is now empty
            try {
                $this->unlink($relationName, $relatedModel, true);
            } catch (InvalidCallException $e) {
                Yii::error("Failed to unlink address!" . $e->getMessage());
                return false;
            }
            $deletedRows = $this->delete();
            return $deletedRows === false ? false : true;
        } else if (!$this->isEmpty && $this->save(false)) {
            if ($this->isNewRecord) {
                try {
                    $this->link($relationName, $relatedModel, ['address_type_id' => $addressTypeId]);
                } catch (InvalidCallException $e) {
                    return false;
                }
            }
            return true;
        } else if ($this->isEmpty) {
            return true;
        }
        return false;
    }

    public function getIsEmpty() {
        return empty($this->street1);
    }

    public function getOneLine() {
        $street = !empty($this->street2) ? $this->street1 . ',' . $this->street2 : $this->street1;
        return $street . ', ' . $this->city . ', ' . $this->state_iso . ', ' . $this->zip;
    }

    /**
     * Ignores attributes param, handles attributes internally
     */

    protected function insertInternal($attributes = null) {
        if (!$this->beforeSave(true)) {
            return false;
        }

        $values = [
            'street1' => $this->street1,
            'street2' => $this->street2,
            'location_id' => $this->location_id
        ];

        // Search for existing Address record
        $address = AddressStreet::find()->where($values)->one();
        if (!is_null($address)) {
            // Record already exists
            $this->id = $values['id'] = $address->id;
            return $this->refresh();
        }

        $address = new AddressStreet();
        foreach ($values as $key => $value) {
            $address->$key = $value;
        }

        if ($address->save()) {
            $this->id = $values['id'] = $address->id;
            $this->setOldAttributes($values);
            $this->afterSave(true, array_fill_keys(array_keys($values), null));
            return true;
        } else {
            return false;
        }
    }

    /**
     *  TODO: Support optimistic locking?
     */

    protected function updateInternal($attributes = null) {
        if (!$this->beforeSave(false)) {
            return false;
        }

        $values = $this->getDirtyAttributes(['street1', 'street2', 'location_id']);

        if (empty($values)) {
            $this->afterSave(false, $values);
            return 0;
        }

        $condition = $this->getOldPrimaryKey(true);
        $lock = $this->optimisticLock();
        if ($lock !== null) {
            $values[$lock] = $this->$lock + 1;
            $condition[$lock] = $this->$lock;
        }

        $addressStreetValues = [
            'street1' => $this->street1,
            'street2' => $this->street2,
            'location_id' => $this->location_id
        ];
        // Search for existing Address record
        $existingAddress = AddressStreet::find()->where($addressStreetValues)->one();

        if (is_null($existingAddress)) {
            $rows = AddressStreet::updateAll($addressStreetValues, $condition);
        } else {
            $thisStreet = AddressStreet::findOne($this->id);
            $rows = static::mergeRedundantRecord($thisStreet, $existingAddress);
            $this->setOldAttribute('id', $this->id);
            $this->id = $existingAddress->id;
            if ($this->refresh() === true) {
                return 1;
            } else {
                return false;
            }
        }

        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $changedAttributes[$name] = $this->getOldAttribute($name) ? $this->getOldAttribute($name) : null;
            $this->setOldAttribute($name, $value);
        }
        $this->afterSave(false, $changedAttributes);
        return $rows;
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            // Fail if address is empty
            if ($this->isEmpty) {
                return false;
            }
            // Location
            $location = AddressLocation::find()
                ->where(
                    [
                        'city' => $this->city,
                        'zip' => $this->zip,
                        'state_id' => $this->state_id
                    ]
                )->one();

            if (is_null($location)) {
                $location = new AddressLocation();
                $location->city = $this->city;
                $location->state_id = $this->state_id;
                $location->zip = $this->zip;
                if (!$location->save()) {
                    return false;
                }
            }
            $this->location_id = $location->id;

            return true;
        }
    }

    public function afterSave($insert, $changedAttributes) {
        if ($insert === false && in_array('location_id', array_keys($changedAttributes))) {
            $this->doLocationGc();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * TODO: Support optimistic locks?
     */

    protected function deleteInternal()
    {
        if (!$this->beforeDelete()) {
            return false;
        }

        $condition = $this->getOldPrimaryKey(true);

        $street = AddressStreet::find()->where($condition)->one();
        if (!empty($street)) {
            try {
                $result = $street->delete();
            } catch (\Exception $e) {
                // Ignore failed delete on the assumption that it is in use by another model
                $result = 0;
            }
        } else {
            return false;
        }

        $this->setOldAttributes(null);
        $this->afterDelete();
        return $result;
    }

    public function afterDelete() {
        $this->doLocationGc();
    }

    /**
     * No optimistic lock support currently
     * @return null
     */

    public function optimisticLock() {
        return null;
    }

    protected function doLocationGc() {
        $db = static::getDb();
        $db->createCommand("DELETE address_location FROM address_location
                    LEFT JOIN address_street ON address_street.location_id = address_location.id
                    WHERE address_street.id IS NULL")->execute();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeople()
    {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])->viaTable('{{%person_address}}', ['address_id' => 'id']);
    }

}