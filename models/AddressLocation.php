<?php

namespace wmc\models;

use Yii;
use wmc\behaviors\FindOrInsertBehavior;

/**
 * This is the model class for table "{{%address_location}}".
 *
 * @property integer $id
 * @property string $city
 * @property integer $state_id
 * @property string $zip
 *
 * @property AddressState $state
 */
class AddressLocation extends \wmc\db\ActiveRecord
{
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
        return '{{%address_location}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

            [['city', 'state_id', 'zip'], 'required']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city' => 'City',
            'state_id' => 'State',
            'zip' => 'Zip',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getState()
    {
        return $this->hasOne(AddressState::className(), ['id' => 'state_id']);
    }
}