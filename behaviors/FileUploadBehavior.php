<?php

namespace wmc\behaviors;

use Yii;
use yii\base\Behavior;
use wmc\models\File;
use wmc\models\FileType;
use yii\db\ActiveRecord;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use wmc\models\FilePath;
use yii\helpers\VarDumper;

class FileUploadBehavior extends Behavior
{
    const DEFAULT_PATH = "@frontend/uploads";

    public $upload_file;

    protected $_path;
    protected $_pathAttribute;
    protected $_fileTypes;
    protected $_fileTypesExclude;
    protected $_minSize;
    protected $_maxSize = 4294967295;
    protected $_required = true;

    protected $_saveFileModel = true;
    protected $_aliasAttribute;
    protected $_inline;
    protected $_status;
    protected $_userGroups = [];

    protected $_uploadedFile;
    protected $_filePath;
    protected $_fileType;
    protected $_actualName;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    public function beforeValidate($event) {
        $this->_uploadedFile = UploadedFile::getInstance($this->owner, 'upload_file');
        if (is_null($this->_uploadedFile)) {
            if ($this->required) {
                $this->owner->addError('upload_file', "File required.");
                $event->isValid = false;
                return false;
            }
        } else {
            // FileType
            $this->_fileType = FileType::findByUploadedFile($this->_uploadedFile);
            if (is_null($this->_fileType)) {
                $this->owner->addError('upload_file', "Unrecognized file type.");
                $event->isValid = false;
                return false;
            }
            // Validate $upload_file
            $model = new DynamicModel(['upload_file' => $this->_uploadedFile]);
            $model->addRule(['upload_file'], 'file', ['mimeTypes' => ArrayHelper::getColumn($this->getFileTypes(), 'mime_type'), 'minSize' => $this->getMinSize(), 'maxSize' => $this->getMaxSize()])->validate();
            if ($model->hasErrors()) {
                $this->owner->addError('upload_file', $model->getFirstError('upload_file'));
                $event->isValid = false;
                return false;
            }
        }
    }

    public function beforeSave($event) {
        if (!is_null($this->_uploadedFile)) {
            // FilePath
            if (!empty($this->_pathAttribute)) {
                $pathValue = $this->owner->{$this->_pathAttribute};
                if (is_numeric($pathValue)) {
                    $this->_filePath = FilePath::findOne($pathValue);
                } else {
                    $this->_filePath = $this->getFilePathModel($pathValue);
                }
            } else {
                $this->_filePath = !empty($this->_path) ? $this->getFilePathModel($this->_path) : $this->getFilePathModel(static::DEFAULT_PATH);
            }
            if (is_null($this->_filePath)) {
                Yii::error("Failed to upload file to specified path. (_path: " . $this->_path . ", _pathAttribute: " . $this->_pathAttribute . ").", 'FileUploadBehavior');
                $this->owner->addError('upload_file', "Failed to upload file to specified path. Please check permissions.");
                $event->isValid = false;
                return false;
            }
            // Save uploaded file to destination path
            $upload = $this->_uploadedFile->saveAs(Yii::getAlias($this->_filePath->path) . DIRECTORY_SEPARATOR . $this->getActualName() . '.' . $this->_fileType->extension);
            if (!$upload) {
                Yii::error("Failed to save uploaded file to destination!
            [Full Path: " . Yii::getAlias($this->_filePath->path) . DIRECTORY_SEPARATOR . $this->getActualName() . '.' . $this->_fileType->extension . "]
            [Uploaded File: Name: " . $this->_uploadedFile->name . " Size: " . $this->_uploadedFile->size . " bytes]", 'FileUploadBehavior');
                $this->owner->addError('upload_file', "Failed to save uploaded file to destination!");
                $event->isValid = false;
                return false;
            }
            // File Model
            if ($this->_saveFileModel === true) {
                $fileAlias = !empty($this->_aliasAttribute) ? $this->owner->{$this->_aliasAttribute} : $this->getActualName();
                $fileValues = [
                    'file_type_id' => $this->_fileType->id,
                    'file_path_id' => $this->_filePath->id,
                    'name' => $this->getActualName(),
                    'alias' => $fileAlias
                ];
                if (!is_null($this->_status)) {
                    $fileValues['status'] = $this->_status;
                }
                if (!is_null($this->_inline)) {
                    $fileValues['inline'] = $this->_inline;
                }
                $file = new File($fileValues);
                $file->userGroupIds = $this->getUserGroups();
                if ($file->save()) {
                    // Link File Model
                    $this->owner->file_id = $file->id;
                } else {
                    Yii::error("Failed to create new File Model from (" . VarDumper::dumpAsString($fileValues) . ") due to (".VarDumper::dumpAsString($file->getErrors()).")");
                    $this->owner->addError('upload_file', "Failed to create new file in database!");
                    $event->isValid = false;
                    return false;
                }
            }
        }
    }

    public function afterDelete($event) {
        if ($this->_saveFileModel) {
            try {
                $file = File::findOne($this->owner->file_id);
                $file->delete();
            } catch (\Exception $e) {
                // stay quiet - file may have been deleted previously
            }
        }
    }

    /**
     * This is the path of the final destination for the uploaded file. Aliases can be used.
     * Required if $this->pathAttribute is not set.
     * @param $path string The alias of the path to store uploaded file
     */

    public function setPath($path) {
        if (is_string($path) && !empty($path)) {
            $this->_path = $path;
        }
    }

    public function getPath() {
        return $this->_path;
    }

    /**
     * Setting this to the attribute name of the behaviors owner will use that value for the destination path.
     * Required if $this->path is not set.
     * @param $pathAttribute string The attribute name that holds the path value
     */

    public function setPathAttribute($pathAttribute) {
        if (is_string($pathAttribute) && !empty($pathAttribute)) {
            $this->_pathAttribute = $pathAttribute;
        }
    }

    public function getPathAttribute() {
        return $this->_pathAttribute;
    }

    /**
     * Refer to wmc/models/FileType for a list of supported file types
     * @param $fileTypeIds array List of FileType ID's to allow.
     */

    public function setFileTypes($fileTypeIds) {
        if (!empty($fileTypeIds)) {
            $this->_fileTypes = FileType::find()->includeTypes($fileTypeIds)->all();
        }
    }

    /**
     * Refer to wmc/models/FileType for a list of supported file types
     * @param $fileTypeIds array List of FileType ID's to NOT allow.
     */

    public function setFileTypesExclude($fileTypeIds) {
        if (!empty($fileTypeIds) && empty($this->fileTypes)) {
            $this->_fileTypes = FileType::find()->excludeTypes($fileTypeIds)->all();
        }
    }

    public function getFileTypes() {
        return $this->_fileTypes;
    }

    /**
     * Set the min file size in bytes. Defaults to NULL.
     * @param $bytes int Range:(1-4294967295)
     */

    public function setMinSize($bytes) {
        if (is_int($bytes) && $bytes > 0 && $bytes <= 4294967295) {
            $this->_minSize = $bytes;
        }
    }

    public function getMinSize() {
        return $this->_minSize;
    }

    /**
     * Set the max file size in bytes. Defaults to 4294967295.
     * @param $bytes int Range:(1-4294967295)
     */

    public function setMaxSize($bytes) {
        if (is_int($bytes) && $bytes > 0 && $bytes <= 4294967295) {
            $this->_maxSize = $bytes;
        }
    }

    public function getMaxSize() {
        return $this->_maxSize;
    }

    /**
     * Enforce whether file upload is required field.
     * @param $required bool Defaults to true
     */

    public function setRequired($required) {
        if (is_bool($required)) {
            $this->_required = $required;
        }
    }

    public function getRequired() {
        return $this->_required;
    }

    /**
     * Setting this to false will not save the File Model, simply validate and upload file.
     * @param $bool bool True saves File Model after upload, Defaults to true
     */

    public function setSaveFileModel($bool) {
        if (is_bool($bool)) {
            $this->_saveFileModel = $bool;
        }
    }

    public function getSaveFileModel() {
        return $this->_saveFileModel;
    }

    public function getFileIdField() {
        return $this->_fileIdField;
    }

    /**
     * This will specify the alias attribute of $this->owner for the file. If this is not set (default) the alias will
     * be derived from uploaded file name.
     * @param $aliasAttribute string Alias attribute of $this->owner for file to be referenced by.
     * See wmc\models\File->alias for more details
     */

    public function setAliasAttribute($aliasAttribute) {
        if (is_string($aliasAttribute) && !empty($aliasAttribute)) {
            $this->_aliasAttribute = $aliasAttribute;
        }
    }

    public function getAliasAttribute() {
        return $this->_aliasAttribute;
    }

    /**
     * Sets File inline property. Only applicable if $this->_saveFileModel is true.
     * @param $bool bool Whether file should appear inline or not
     */

    public function setInline($bool) {
        if (is_bool($bool)) {
            $this->_inline = $bool;
        }
    }

    public function getInline() {
        return $this->_inline;
    }

    /**
     * Sets File status property. Only applicable if $this->_saveFileModel is true.
     * @param $status integer Status of File Model, defaults to 1
     */

    public function setStatus($status) {
        if (is_int($status)) {
            $this->_status = $status;
        }
    }

    public function getStatus() {
        return $this->_status;
    }

    /**
     * UserGroups that will be linked to this file. Only applicable if $this->_saveFileModel is true.
     * @param $userGroups array UserGroup ID's that are allowed to access this file.
     */

    public function setUserGroups($userGroups) {
        if (is_array($userGroups) && !empty($userGroups)) {
            $this->_userGroups = $userGroups;
        }
    }

    public function getUserGroups() {
        return $this->_userGroups;
    }

    protected function normalizeUploadedFileName() {
        $normalizedName = static::sanitizeUploadedFileName($this->_uploadedFile->baseName);
        $count = 0;
        while (file_exists(Yii::getAlias($this->getPath() . DIRECTORY_SEPARATOR . $normalizedName . '.' . $this->_fileType->extension))) {
            $count++;
            $normalizedName = static::sanitizeUploadedFileName($this->_uploadedFile->baseName) . '_' . $count;
        }
        return $normalizedName;
    }

    public static function sanitizeUploadedFileName($name) {
        return preg_replace("/[^A-Za-z0-9\-_]/", '', str_replace(['.', ':', ';', "'", '~'], '-', str_replace(' ', '_', $name)));
    }

    protected function getActualName() {
        if (empty($this->_actualName)) {
            $this->_actualName = $this->normalizeUploadedFileName();
        }
        return $this->_actualName;
    }

    protected function getFilePathModel($path) {
        if (@is_dir(Yii::getAlias($path)) && is_writable(Yii::getAlias($path))) {
            return FilePath::findByPath($path);
        }
        return false;
    }

}