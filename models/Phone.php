<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%phone}}".
 *
 * @property integer $id
 * @property string $area_code
 * @property string $number
 * @property string $extension
 *
 * @property OrganizationPhone[] $organizationPhones
 * @property PersonPhone[] $personPhones
 */
class Phone extends \wmc\db\ActiveRecord
{
    const TYPE_DIRECT = 1;
    const TYPE_MOBILE = 2;
    const TYPE_OFFICE = 3;
    const TYPE_HOME = 4;
    const TYPE_FAX = 5;

    public $full;
    public $type_id;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%phone}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['full'], 'required', 'on' => 'require', 'message' => 'Phone cannot be blank.'],
            [['type_id'], 'integer'],
            [['type_id'], 'required', 'on' => 'require', 'message' => 'Phone type cannot be blank.'],
            [['full'], 'match', 'pattern' => '/^\([0-9]{3}\)[0-9]{3}\-[0-9]{4}$/', 'message' => 'Invalid phone format. Use: (999)999-9999'],
            [['full'], 'convertFull'],
            [['area_code', 'number'], 'required', 'on' => 'require'],
            [['area_code'], 'string', 'max' => 3],
            [['number'], 'string', 'max' => 7],
            [['extension'], 'string', 'max' => 5],
            [['area_code', 'number', 'extension'], 'match', 'pattern' => '/[0-9]/'],
            [['area_code', 'number', 'extension'], 'unique', 'targetAttribute' => ['area_code', 'number', 'extension'], 'message' => 'This phone number is already in use.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'area_code' => 'Area Code',
            'number' => 'Number',
            'extension' => 'Extension',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrganizationPhones()
    {
        return $this->hasMany(OrganizationPhone::className(), ['phone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersonPhones()
    {
        return $this->hasMany(PersonPhone::className(), ['phone_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPersons()
    {
        return $this->hasMany(Person::className(), ['id' => 'phone_id'])->viaTable('{{%person_phone}}', ['phone_id' => 'id']);
    }

    public function convertFull($attribute, $params) {
        if (preg_match('/^\(([0-9]{3})\)([0-9]{3})\-([0-9]{4})$/', $this->$attribute, $match)) {
            $this->area_code = $match[1];
            $this->number = $match[2] . $match[3];
        }
    }

    public function afterFind() {
        $this->full = '(' . $this->area_code . ')' . substr($this->number, 0, 3) . '-' . substr($this->number, 3, 4);
        parent::afterFind();
    }

    /**
     * Creates an array suitable for dropDownLists, etc.
     * @param array $typeIds An array of type ids that should be included in results, results will stay ordered
     * @return array A list of all the defined phone types keyed by the type ID
     */

    public static function getTypeList($typeIds = []) {
        $allTypes = $types = [];
        $reflection = new \ReflectionClass(self::className());
        foreach ($reflection->getConstants() as $key => $val) {
            if (substr($key, 0, 5) == 'TYPE_') {
                $allTypes[$val] = ucwords(strtolower(substr($key, 5)));
            }
        }
        if (!empty($typeIds)) {
            foreach ($typeIds as $typeId) {
                $types[$typeId] = $allTypes[$typeId];
            }
        } else {
            $types = $allTypes;
        }
        return $types;
    }
}