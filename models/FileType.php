<?php

namespace wmc\models;

use Yii;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "common.file_type".
 *
 * @property integer $id
 * @property string $name
 * @property string $extension
 * @property string $mime_type
 * @property integer $allow_inline
 */
class FileType extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.file_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'extension'], 'required'],
            [['allow_inline'], 'integer'],
            [['name', 'mime_type'], 'string', 'max' => 50],
            [['extension'], 'string', 'max' => 5],
            [['name'], 'unique'],
            [['extension'], 'unique']
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
            'extension' => 'Extension',
            'mime_type' => 'Mime Type',
            'allow_inline' => 'Allow Inline',
        ];
    }

    /**
     * Should be called BEFOREE move_uploaded_file call!
     * @param $uploadedFile UploadedFile
     * @return FileType Tries to find FileType based first on extension but falls back to first available mimi-type match (sorted by id ASC)
     */

    public static function findByUploadedFile($uploadedFile) {
        $fileType = null;
        // Check by extension first
        if (!empty($uploadedFile->extension)) {
            $fileType = static::find()->where(['extension' => strtolower($uploadedFile->extension)])->one();
        }
        if (empty($fileType)) {
            // Try and find by MIME (using FileHelper to get actual MIME type)
            $mimeType = FileHelper::getMimeType($uploadedFile->tempName);
            if (!empty($mimeType)) {
                $fileType = static::find()->where(['mime_type' => $mimeType])->orderBy(['id' => SORT_ASC])->limit(1)->one();
            }
        }
        return $fileType;
    }
}