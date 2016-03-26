<?php

namespace wmc\models;

use Yii;
use wmc\models\user\UserGroup;
use wmc\behaviors\TimestampBehavior;
use wmc\behaviors\UserGroupAccessBehavior;
use wmc\behaviors\FileUploadBehavior;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "file".
 *
 * @property integer $id
 * @property integer $file_type_id
 * @property integer $file_path_id
 * @property string $name
 * @property string $alias
 * @property integer bytes
 * @property integer $inline
 * @property integer $status
 * @property string $updated_at
 * @property string $created_at
 *
 * @property FileType $fileType
 * @property FilePath $filePath
 * @property UserGroup[] $userGroups
 * @property FileLog[] $fileLogs
 */
class File extends \wmc\db\ActiveRecord
{
    const SCENARIO_UPDATE = 'update';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPDATE] = ['file_path_id', 'alias', 'inline', 'status', 'updated_at', 'userGroupIds'];
        return $scenarios;
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className()
            ],
            [
                'class' => UserGroupAccessBehavior::className(),
                'viaTableName' => '{{%file_access}}',
                'itemIdField' => 'file_id'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_type_id', 'file_path_id', 'name'], 'required'],
            [['file_type_id', 'file_path_id', 'inline', 'status'], 'integer'],
            [['status', 'inline'], 'default', 'value' => 1],
            [['file_type_id', 'file_path_id', 'inline', 'status'], 'filter', 'filter' => 'intval'],
            [['bytes'], 'integer', 'min' => 0, 'max' => 4294967295],
            [['updated_at', 'created_at'], 'safe'],
            [['name', 'alias'], 'string', 'max' => 255],
            [['alias'], 'default', 'value' => function ($model, $attribute) {
                return $model->name;
            }],
            [['name', 'alias'], 'match', 'not' => true, 'pattern' => "/[^A-Za-z0-9\-_]/"],
            [['file_path_id', 'name', 'file_type_id'], 'unique', 'targetAttribute' => ['file_path_id', 'name', 'file_type_id'], 'message' => 'That path and name has already been taken.'],
            [['file_path_id', 'alias', 'file_type_id'], 'unique', 'targetAttribute' => ['file_path_id', 'alias', 'file_type_id'], 'message' => 'That path and alias combination has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file_type_id' => 'File Type ID',
            'file_path_id' => 'File Path ID',
            'name' => 'Name',
            'alias' => 'Alias',
            'bytes' => 'Size',
            'inline' => 'Inline',
            'status' => 'Status',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFileType()
    {
        return $this->hasOne(FileType::className(), ['id' => 'file_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFilePath()
    {
        return $this->hasOne(FilePath::className(), ['id' => 'file_path_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFileLogs()
    {
        return $this->hasMany(FileLog::className(), ['file_id' => 'id']);
    }

    public static function findFileFromFilename($filename, $pathAlias = '') {
        if (empty($filename) || !is_string($filename)) {
            return null;
        }

        $pathinfo = pathinfo($filename);
        $alias = $pathinfo['filename'];
        $extension = $pathinfo['extension'];

        if (empty($alias) || empty($extension)) {
            return null;
        } else {
            return static::find()->where([static::tableName() . '.alias' => $alias, 'extension' => $extension, FilePath::tableName() . '.alias' => $pathAlias, 'status' => 1])->joinWith(['fileType', 'filePath'])->one();
        }
    }

    public function getFullName() {
        return $this->name . '.' . $this->fileType->extension;
    }

    public function getFullAlias() {
        return $this->alias . '.' . $this->fileType->extension;
    }

    public function afterDelete() {
        // Attempt to remove file
        $filePath = FilePath::findOne($this->file_path_id);
        $fileType = FileType::findOne($this->file_type_id);
        if (!empty($filePath) && !empty($fileType)) {
            $filename = Yii::getAlias($filePath->path) . DIRECTORY_SEPARATOR . $this->name . '.' . $fileType->extension;
            @unlink($filename);
        }
        parent::afterDelete();
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            // Set bytes field on insert
            if ($insert) {
                $this->bytes = @filesize(Yii::getAlias($this->filePath->path) . DIRECTORY_SEPARATOR . $this->fullName);
            }
            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes) {
        if (!$insert) {
            // Path
            if (in_array('file_path_id', array_keys($changedAttributes))) {
                $fileType = FileType::findOne($this->file_type_id);
                $newFilePath = FilePath::findOne($this->file_path_id);
                $newFileName = static::normalizeFileName($this->name, $fileType->extension, $newFilePath->path);
                $oldFilePath = FilePath::findOne($changedAttributes['file_path_id']);
                $oldFullPath = Yii::getAlias($oldFilePath->path) . DIRECTORY_SEPARATOR . $this->name . '.' . $fileType->extension;
                $newFullPath = Yii::getAlias($newFilePath->path) . DIRECTORY_SEPARATOR . $newFileName . '.' . $fileType->extension;
                if (rename($oldFullPath, $newFullPath)) {
                    // If name has changed, update that
                    if ($this->name != $newFileName) {
                        $this->name = $newFileName;
                        if (!$this->save(true, ['name'])) {
                            Yii::error("Failed to set new file name (".$newFileName.") for file ID: ".$this->id.".", "File");
                            $this->rollbackPath($changedAttributes['file_path_id']);
                        }
                    }
                } else {
                    Yii::error("Failed to move file from (".$oldFullPath.") to (".$newFullPath.") on file ID: ".$this->id.".", "File");
                    $this->rollbackPath($changedAttributes['file_path_id']);
                }
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public static function normalizeFileName($fileName, $fileExtension, $destinationPath) {
        $normalizedName = static::sanitizeFileName($fileName);
        $count = 0;
        while (file_exists(Yii::getAlias($destinationPath . DIRECTORY_SEPARATOR . $normalizedName . '.' . $fileExtension))) {
            $count++;
            $normalizedName = static::sanitizeFileName($fileName) . '_' . $count;
        }
        return $normalizedName;
    }

    public static function sanitizeFileName($name) {
        return preg_replace("/[^A-Za-z0-9\-_]/", '', str_replace(['.', ':', ';', "'", '~'], '-', str_replace(' ', '_', $name)));
    }

    protected function rollbackPath($pathId) {
        $this->file_path_id = $pathId;
        $this->save(true, ['file_path_id']);
    }
}