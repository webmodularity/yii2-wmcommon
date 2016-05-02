<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.file_type_category".
 *
 * @property integer $id
 * @property string $name
 */
class FileTypeCategory extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.file_type_category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 20],
            [['name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }
}