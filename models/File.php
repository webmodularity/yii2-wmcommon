<?php

namespace wmc\models;

use Yii;
use wmc\models\user\UserGroup;
use yii\base\InvalidConfigException;
use wmc\behaviors\UserGroupAccessBehavior;

/**
 * NEEDS UPDATED DOCS!! These are for FileData, File is now a View
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

    public static function primaryKey() {
        return ['id'];
    }

    public function behaviors() {
        return [
            [
                'class' => UserGroupAccessBehavior::className(),
                'viaTableName' => '{{%file_access}}',
                'itemIdField' => 'file_id'
            ]
        ];
    }

    public static function find()
    {
        return new FileQuery(get_called_class());
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            throw new InvalidConfigException("The File model does not support save! Use the FileData model instead.");
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file_type_id' => 'File Type ID',
            'file_type_name' => 'File Type',
            'file_type_allow_inline' => 'File Type Allow Inline',
            'file_type_category_name' => 'File Type Category',
            'file_path_id' => 'File Path ID',
            'path' => 'Path',
            'path_alias' => 'Path Alias',
            'name' => 'Name',
            'extension' => 'Extension',
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
    public function getFileLogs()
    {
        return $this->hasMany(FileLog::className(), ['file_id' => 'id']);
    }

    public function getFullName() {
        return $this->name . '.' . $this->extension;
    }

    public function getFullAlias() {
        return $this->alias . '.' . $this->extension;
    }

    public function getUrl($fileAlias = 'file') {
        $pathAlias = !empty($this->path_alias) ? $this->path_alias . DIRECTORY_SEPARATOR : '';
        return DIRECTORY_SEPARATOR . $fileAlias . DIRECTORY_SEPARATOR . $pathAlias . $this->getFullAlias();
    }
}