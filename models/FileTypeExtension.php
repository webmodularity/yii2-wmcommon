<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.file_type_extension".
 *
 * @property integer $file_type_id
 * @property string $extension
 * @property integer $is_primary
 */
class FileTypeExtension extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.file_type_extension';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_type_id', 'extension'], 'required'],
            [['file_type_id', 'is_primary'], 'integer'],
            [['extension'], 'string', 'max' => 5],
            [['extension'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'file_type_id' => 'File Type ID',
            'extension' => 'Extension',
            'is_primary' => 'Is Primary',
        ];
    }
}