<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.file_type_mime".
 *
 * @property integer $file_type_id
 * @property string $mime_type
 */
class FileTypeMime extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.file_type_mime';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_type_id', 'mime_type'], 'required'],
            [['file_type_id'], 'integer'],
            [['mime_type'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'file_type_id' => 'File Type ID',
            'mime_type' => 'Mime Type',
        ];
    }
}