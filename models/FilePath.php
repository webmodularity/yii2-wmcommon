<?php

namespace wmc\models;

use Yii;
use yii\helpers\ArrayHelper;

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
            [['path', 'alias'], 'string', 'max' => 255],
            [['path', 'alias'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Full Path',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(File::className(), ['file_path_id' => 'id']);
    }

    public function afterSave($insert, $changedAttributes) {
        if ($insert) {
            $oldMask = umask(0);
            @mkdir(Yii::getAlias($this->path), 0777);
            umask($oldMask);
        } else if (in_array('path', array_keys($changedAttributes))) {
            @rename(Yii::getAlias($changedAttributes['path']), Yii::getAlias($this->path));
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete() {
        @rmdir(Yii::getAlias($this->path));

        parent::afterDelete();
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

    public static function getFilePathList() {
        return ArrayHelper::map(self::find()->orderBy(['path' => SORT_ASC])->all(), 'id', 'path');
    }
}