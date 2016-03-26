<?php

namespace wmc\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use wmc\models\FileTypeQuery;
use wmc\models\FileTypeMime;

/**
 * This is the model class for table "common.file_type".
 *
 * @property integer $id
 * @property string $name
 * @property string $extension
 * @property integer $allow_inline
 */
class FileType extends \wmc\db\ActiveRecord
{
    protected $_iconNames = [
        1 => 'file-pdf-o'
    ];

    public static function find()
    {
        return new FileTypeQuery(get_called_class());
    }

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
            [['name'], 'string', 'max' => 50],
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
            'allow_inline' => 'Allow Inline',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMimeTypes()
    {
        return $this->hasMany(FileTypeMime::className(), ['file_type_id' => 'id']);
    }

    /**
     * Should be called BEFORE move_uploaded_file call!
     * @param $uploadedFile \yii\web\UploadedFile
     * @return FileType Tries to find FileType based first on extension but falls back to first available mimi-type match (sorted by id ASC)
     */

    public static function findByUploadedFile($uploadedFile) {
        // Try and find by MIME (using FileHelper to get actual MIME type)
        $mimeType = FileHelper::getMimeType($uploadedFile->tempName);
        if (!empty($mimeType)) {
            return static::find()->joinWith(['mimeTypes'])->where(['mime_type' => $mimeType])->limit(1)->one();
        } else {
            return null;
        }
    }

    public function getIconName() {
        return isset($this->_iconNames[$this->id]) ? $this->_iconNames[$this->id] : 'file-o';
    }

    public static function getFileTypeList($excludeIds = [], $includeIds = []) {
        return ArrayHelper::map(FileType::find()->includeTypes($includeIds)->excludeTypes($excludeIds)->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');
    }
}