<?php

namespace wmc\behaviors;

use Yii;
use wmc\behaviors\AttributeBehavior;
use wmc\models\FileData;
use wmc\models\FileType;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\web\UploadedFile;
use wmc\models\FilePath;
use yii\validators\Validator;
use yii\helpers\VarDumper;
use yii\helpers\FileHelper;

class FileUploadBehavior extends AttributeBehavior
{
    static $attributeConfigDefault = [
        'path' => null,
        'fileTypes' => [],
        'minSize' => null,
        'maxSize' => 4294967295,
    ];

    public $fileUpload;

    protected $_uploadedFiles = [];

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate'
        ];
    }

    public function setAttributes($attributes) {
        if (is_array($attributes)) {
            foreach ($attributes as $attributeName => $attributeConfig) {
                if (empty($attributeName) || is_int($attributeName)) {
                    $attributeName = 'fileUpload';
                }
                $this->_attributes[$attributeName] = $this->processAttributeConfig($attributeName, $attributeConfig);
            }
        }
    }

    protected function processAttributeConfig($attributeName, $attributeConfig) {
        $config = static::$attributeConfigDefault;
        foreach ($config as $name => $defaultVal) {
            $val = isset($attributeConfig[$name]) ? $attributeConfig[$name] : $defaultVal;
            switch($name) {
                case 'path':
                    if (!empty($val) && is_string($val)) {
                        $filePath = FilePath::find()->where(['path' => $val])->limit(1)->one();
                        if (!is_null($filePath)) {
                            $config[$name] = $filePath;
                        }
                    }
                    break;
                case 'fileTypes':
                    if (!empty($val) && (is_string($val) || is_array($val))) {
                        $normalizedVal = is_string($val) ? [$val] : $val;
                        $fileTypeNames = [];
                        foreach ($normalizedVal as $fileType) {
                            if (is_string($fileType) && !in_array($fileType, $fileTypeNames)) {
                                $fileTypeNames[] = $fileType;
                            }
                        }
                        if (!empty($fileTypeNames)) {
                            $config[$name] = FileType::find()->inName($fileTypeNames)->all();
                        }
                    }
                    if (empty($config[$name])) {
                        // Need to define at least 1 file type
                        throw new InvalidConfigException("No valid FileType names specified!");
                    }
                    break;
                case 'minSize':
                case 'maxSize':
                if (is_int($val) && $val > 0 && $val <= 4294967295) {
                    $config[$name] = $val;
                }
                break;
            }
        }
        return $config;
    }

    public function attach($owner) {
        parent::attach($owner);

        foreach ($this->_attributes as $attributeName => $settings) {
            $owner->validators->append(Validator::createValidator('file', $owner, [$attributeName], [
                'mimeTypes' => $this->getAllowedMimeTypes($attributeName),
                'minSize' => $settings['minSize'],
                'maxSize' => $settings['minSize']
            ]));
        }
    }

    public function beforeValidate($event) {
        foreach ($this->_attributes as $attributeName => $attributeConfig) {
            $this->owner->$attributeName = UploadedFile::getInstance($this->owner, $attributeName);
            $uploadedFile = $this->owner->$attributeName;

            if (!is_null($uploadedFile)) {
                // FileType
                $mimeType = FileHelper::getMimeType($uploadedFile->tempName);
                if (empty($mimeType)) {
                    Yii::error("Failed to determine type of uploaded file. UploadedFile: ("
                        . VarDumper::dumpAsString($uploadedFile)
                        . ")."
                        , 'FileUploadBehavior');
                    $this->owner->addError($attributeName, "Unrecognized file type.");
                    $event->isValid = false;
                    return false;
                } else {
                    $this->_uploadedFiles[$attributeName]['type'] = FileType::find()->fromMimeType($mimeType)->limit(1)->one();
                }
                // FilePath
                if ($attributeConfig['path'] instanceof FilePath) {
                    $this->_uploadedFiles[$attributeName]['path'] = $attributeConfig['path'];
                } else {
                    $this->_uploadedFiles[$attributeName]['path'] = FilePath::findOne($this->owner->file_path_id);
                }
                // Normalized FileName
                $this->_uploadedFiles[$attributeName]['normalizedName'] = FileData::normalizeFileName(
                    $this->owner->$attributeName->baseName,
                    $this->_uploadedFiles[$attributeName]['type']->extension,
                    $this->_uploadedFiles[$attributeName]['path']->path
                );
            } else {
                if ($this->owner->isAttributeChanged($attributeName) && empty($this->owner->$attributeName)) {
                    $this->owner->$attributeName = $this->owner->getOldAttribute($attributeName);
                }
            }
        }
    }

    public function beforeInsert($event) {
        foreach (array_keys($this->_attributes) as $attributeName) {
            $uploadedFile = $this->owner->$attributeName;

            if (!is_null($uploadedFile)) {
                // Save uploaded file to destination path
                $upload = $uploadedFile->saveAs(Yii::getAlias($this->_uploadedFiles[$attributeName]['path']->path)
                    . DIRECTORY_SEPARATOR
                    . $this->_uploadedFiles[$attributeName]['normalizedName']
                    . '.'
                    . $this->_uploadedFiles[$attributeName]['type']->extension);
                // Set bytes
                if (!$upload) {
                    Yii::error("Failed to save uploaded file to destination!
                                [Full Path: "
                                    . Yii::getAlias($this->_uploadedFiles[$attributeName]['path']->path)
                                    . DIRECTORY_SEPARATOR
                                    . $this->_uploadedFiles[$attributeName]['normalizedName']
                                    . '.'
                                    . $this->_uploadedFiles[$attributeName]['type']->extension
                                    . "]
                                [Uploaded File: "
                                    . "Name: " . $uploadedFile->name
                                    . " Size: " . $uploadedFile->size . " bytes"
                                    . "]"
                        , 'FileUploadBehavior');
                    $this->owner->addError($attributeName, "Failed to save uploaded file to destination!");
                    $event->isValid = false;
                    return false;
                }
            }
        }
    }

    public function beforeUpdate($event) {

    }

    public function getAllowedMimeTypes($attribute) {
        $mimeTypes = [];
        foreach ($this->_attributes[$attribute]['fileTypes'] as $fileType) {
            foreach ($fileType->mimeTypes as $mimeType) {
                $mimeTypes[] = $mimeType->mime_type;
            }
        }
        return $mimeTypes;
    }

    public function getAllowedFileExtensions($attribute, $primaryOnly = false) {
        $extensions = [];
        foreach ($this->_attributes[$attribute]['fileTypes'] as $fileType) {
            if ($primaryOnly) {
                $extensions[] = $fileType->extension;
            } else {
                foreach ($fileType->extensions as $extension) {
                    $extensions[] = $extension->extension;
                }
            }
        }
        return $extensions;
    }

    public function getAllowedFileExtensionsHint($attribute) {
        $sorted = $this->getAllowedFileExtensions($attribute, true);
        sort($sorted);
        return 'Accepts ' . Inflector::sentence($sorted) . ' file types.';
    }

    public function getAllowedFileTypes($attribute) {
        return $this->_attributes[$attribute]['fileTypes'];
    }

    public function getUploadedFileModel($attribute = null) {
        return $this->owner;
    }


    public function getUploadedFileName($attribute = null) {
        return $this->getUploadedFileModel($attribute)->fullName;
    }
}