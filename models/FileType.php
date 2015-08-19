<?php

namespace wmc\models;

use Yii;

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

    public static function findByExtension($extension) {
        return static::find()->where(['extension' => strtolower($extension)])->one();
    }
}