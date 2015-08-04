<?php

namespace wmc\models;

use Yii;
use wmc\models\user\UserGroup;
use wmc\behaviors\TimestampBehavior;

/**
 * This is the model class for table "file".
 *
 * @property integer $id
 * @property integer $file_type_id
 * @property integer $file_path_id
 * @property string $name
 * @property string $alias
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
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className()
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_type_id', 'file_path_id', 'name', 'alias'], 'required'],
            [['file_type_id', 'file_path_id', 'inline', 'status'], 'integer'],
            [['updated_at', 'created_at'], 'safe'],
            [['name', 'alias'], 'string', 'max' => 255],
            [['file_path_id', 'name', 'file_type_id'], 'unique', 'targetAttribute' => ['file_path_id', 'name', 'file_type_id'], 'message' => 'The combination of File Type ID, File Path ID and Name has already been taken.'],
            [['alias', 'file_type_id'], 'unique', 'targetAttribute' => ['alias', 'file_type_id'], 'message' => 'The combination of File Type ID and Alias has already been taken.']
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


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserGroups()
    {
        return $this->hasMany(UserGroup::className(), ['id' => 'user_group_id'])->viaTable('{{%file_access}}', ['file_id' => 'id']);
    }

    public static function findFileFromFilename($filename) {
        if (empty($filename) || !is_string($filename)) {
            return null;
        }

        $pathinfo = pathinfo($filename);
        $alias = $pathinfo['filename'];
        $extension = $pathinfo['extension'];

        if (empty($alias) || empty($extension)) {
            return null;
        } else {
            return static::find()->where(['alias' => $alias, 'extension' => $extension,'status' => 1])->joinWith('fileType')->one();
        }
    }

    public function getFullName() {
        return $this->name . '.' . $this->fileType->extension;
    }

    public function getFullAlias() {
        return $this->alias . '.' . $this->fileType->extension;
    }

    public function groupHasAccess($groupId = 0) {
        $fileId = $this->id;
        if (!empty($fileId) && is_int($groupId)) {
            $access = $this->getUserGroups()->where(['id' => $groupId])->count();
            return $access > 0 ? true : false;
        }
        return false;
    }
}