<?php

namespace wmc\behaviors;

use Yii;
use yii\base\Behavior;
use wmc\models\File;
use wmc\models\FileType;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use wmc\models\FilePath;
use yii\validators\Validator;
use yii\helpers\VarDumper;

class FileUploadBehavior extends Behavior
{
    public $upload_file;

    protected $_path = "@frontend/uploads";
    protected $_fileTypes = [];
    protected $_fileTypesExclude;
    protected $_minSize;
    protected $_maxSize = 4294967295;
    protected $_uploadRequired = true;

    protected $_uploadedFileType;
    protected $_uploadedFileNormalizedName;
    protected $_uploadedFilePath;

    protected $_saveFileModel = true;
    protected $_inline;
    protected $_status;
    protected $_userGroups = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    public function attach($owner) {
        parent::attach($owner);

        $skipOnEmpty = $this->getUploadRequired() ? false : true;
        $owner->validators->append(Validator::createValidator('file', $owner, ['upload_file'], [
            'skipOnEmpty' => $skipOnEmpty,
            'mimeTypes' => $this->getMimeTypes(),
            'minSize' => $this->getMinSize(),
            'maxSize' => $this->getMaxSize()
        ]));
    }

    public function beforeValidate($event) {
        $this->owner->upload_file = UploadedFile::getInstance($this->owner, 'upload_file');
        $uploadedFile = $this->owner->upload_file;

        if (!is_null($uploadedFile)) {
            // FileType
            $this->_uploadedFileType = FileType::findByUploadedFile($uploadedFile);
            if (is_null($this->_uploadedFileType)) {
                Yii::error("Failed to determine type of uploaded file. UploadedFile: (" . VarDumper::dumpAsString($uploadedFile) . ").", 'FileUploadBehavior');
                $this->owner->addError('upload_file', "Unrecognized file type.");
                $event->isValid = false;
                return false;
            }
            // FilePath
            if ($this->_saveFileModel) {
                if (@is_dir(Yii::getAlias($this->_path)) && is_writable(Yii::getAlias($this->_path))) {
                    $this->_uploadedFilePath = FilePath::findByPath($this->_path);
                }
            } else {
                $this->_uploadedFilePath = FilePath::findOne($this->owner->file_path_id);
            }
            if (is_null($this->_uploadedFilePath)) {
                Yii::error("Failed to upload file to specified path. (_path: " . $this->_path . ").", 'FileUploadBehavior');
                $this->owner->addError('upload_file', "Failed to upload file to specified path. Please check permissions.");
                $event->isValid = false;
                return false;
            }
            // Normalized FileName
            $this->_uploadedFileNormalizedName = File::normalizeFileName(
                $this->owner->upload_file->baseName,
                $this->_uploadedFileType->extension,
                $this->_uploadedFilePath->path
            );
            if (!$this->_saveFileModel) {
                $this->owner->file_type_id = $this->_uploadedFileType->id;
                $this->owner->name = $this->_uploadedFileNormalizedName;
            }
        }
    }

    public function beforeSave($event) {
        $uploadedFile = $this->owner->upload_file;
        if (!is_null($uploadedFile)) {
            // Save uploaded file to destination path
            $upload = $uploadedFile->saveAs(Yii::getAlias($this->_uploadedFilePath->path) . DIRECTORY_SEPARATOR . $this->_uploadedFileNormalizedName . '.' . $this->_uploadedFileType->extension);
            if (!$upload) {
                Yii::error("Failed to save uploaded file to destination!
            [Full Path: " . Yii::getAlias($this->_uploadedFilePath->path) . DIRECTORY_SEPARATOR . $this->_uploadedFileNormalizedName . '.' . $this->_uploadedFileType->extension . "]
            [Uploaded File: Name: " . $uploadedFile->name . " Size: " . $uploadedFile->size . " bytes]", 'FileUploadBehavior');
                $this->owner->addError('upload_file', "Failed to save uploaded file to destination!");
                $event->isValid = false;
                return false;
            }
            // File Model
            if ($this->_saveFileModel) {
                $fileValues = [
                    'file_type_id' => $this->_uploadedFileType->id,
                    'file_path_id' => $this->_uploadedFilePath->id,
                    'name' => $this->_uploadedFileNormalizedName
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

    /**
     * Refer to wmc/models/FileType for a list of supported file types
     * @param $fileTypeIds array List of FileType ID's to allow.
     */

    public function setFileTypes($fileTypeIds) {
        if (!empty($fileTypeIds)) {
            $this->_fileTypes = FileType::find()->joinWith('mimeTypes')->includeTypes($fileTypeIds)->all();
        }
    }

    /**
     * Refer to wmc/models/FileType for a list of supported file types
     * @param $fileTypeIds array List of FileType ID's to NOT allow.
     */

    public function setFileTypesExclude($fileTypeIds) {
        if (!empty($fileTypeIds) && empty($this->fileTypes)) {
            $this->_fileTypes = FileType::find()->joinWith('mimeTypes')->excludeTypes($fileTypeIds)->all();
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

    public function setUploadRequired($required) {
        if (is_bool($required)) {
            $this->_uploadRequired = $required;
        }
    }

    public function getUploadRequired() {
        return $this->_uploadRequired;
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

    protected function getMimeTypes() {
        $mimeTypes = [];
        foreach ($this->getFileTypes() as $fileType) {
            foreach ($fileType->mimeTypes as $mimeType) {
                $mimeTypes[] = $mimeType->mime_type;
            }
        }
        return $mimeTypes;
    }

}