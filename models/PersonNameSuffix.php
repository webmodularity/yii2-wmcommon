<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.person_name_suffix".
 *
 * @property integer $id
 * @property string $suffix
 * @property integer $ordering
 */
class PersonNameSuffix extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.person_name_suffix';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['suffix', 'ordering'], 'required'],
            [['ordering'], 'integer'],
            [['suffix'], 'string', 'max' => 10],
            [['suffix'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suffix' => 'Suffix',
            'ordering' => 'Ordering',
        ];
    }
}