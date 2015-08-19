<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%file_path}}".
 *
 * @property integer $id
 * @property string $path
 *
 * @property File[] $files
 */
class FilePath extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file_path}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['path'], 'required'],
            [['path'], 'string', 'max' => 255],
            [['path'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Path',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(File::className(), ['file_path_id' => 'id']);
    }

    public static function findByPath($path) {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $filePath = static::find()->where(['path' => $path])->one();
        if (empty($filePath)) {
            $filePath = new FilePath(
                [
                    'path' => $path,
                    'alias' => basename($path)
                ]
            );
            if (!$filePath->save()) {
                return null;
            }
        }
        return $filePath;
    }
}