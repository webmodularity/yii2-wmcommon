<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.person_name_prefix".
 *
 * @property integer $id
 * @property string $prefix
 * @property integer $ordering
 */
class PersonNamePrefix extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.person_name_prefix';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ordering'], 'required'],
            [['ordering'], 'integer'],
            [['prefix'], 'string', 'max' => 10],
            [['prefix'], 'unique']
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
            'ordering' => 'Ordering',
        ];
    }
}