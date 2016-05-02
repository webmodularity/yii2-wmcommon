<?php

namespace wmc\behaviors;

use Yii;
use wmc\behaviors\FileUploadBehavior;
use yii\db\ActiveRecord;
use wmc\models\FilePath;
use wmc\models\FileData;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;

class FileUploadRelatedBehavior extends FileUploadBehavior
{
    static $attributeRelatedConfigDefault = [
        'inline' => true,
        'status' => true,
        'relationName' => null,
        'userGroups' => []
    ];

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    protected function processAttributeConfig($attributeName, $attributeConfig) {
        $config = parent::processAttributeConfig($attributeName, $attributeConfig);
        $relatedConfig = static::$attributeRelatedConfigDefault;
        foreach ($relatedConfig as $name => $defaultVal) {
            $val = isset($attributeConfig[$name]) ? $attributeConfig[$name] : $defaultVal;
            switch ($name) {
                case 'inline':
                case 'status':
                    if (is_bool($val)) {
                        $config[$name] = (int)$val;
                    } else if (is_int($val)) {
                        $config[$name] = $val;
                    }
                    break;
                case 'userGroups':
                    if (is_array($val)) {
                        $config[$name] = $val;
                    }
                    break;
                case 'relationName':
                    if (!empty($val) && is_string($val)) {
                        $config[$name] = $val;
                    } else if (is_null($val)) {
                        // Try and guess relationName
                        $config[$name] = lcfirst(Inflector::camelize(substr($attributeName, 0, -3)));
                    }
            }
        }
        return $config;
    }

    public function beforeInsert($event) {
        parent::beforeInsert($event);


        foreach ($this->_attributes as $attributeName => $settings) {
            $uploadedFile = $this->owner->$attributeName;

            if (!is_null($uploadedFile)) {
                $fileValues = [
                    'file_type_id' => $this->_uploadedFiles[$attributeName]['type']->id,
                    'file_path_id' => $this->_uploadedFiles[$attributeName]['path']->id,
                    'name' => $this->_uploadedFiles[$attributeName]['normalizedName'],
                    'status' => $settings['status'],
                    'inline' => $settings['inline']
                ];
                $file = new FileData($fileValues);
                $file->userGroupIds = $settings['userGroups'];
                if ($file->save()) {
                    // Link File Model
                    $this->owner->$attributeName = $file->id;
                } else {
                    Yii::error("Failed to create new File Model from ("
                        . VarDumper::dumpAsString($fileValues) . ") due to ("
                        . VarDumper::dumpAsString($file->getErrors()) . ")");
                    $this->owner->addError($attributeName, "Failed to create new file in database!");
                    $event->isValid = false;
                    return false;
                }
            }
        }
    }

    public function beforeUpdate($event) {

    }

    public function afterDelete($event) {
        foreach (array_keys($this->_attributes) as $attributeName) {
            try {
                $file = FileData::findOne($this->owner->$attributeName);
                $file->delete();
            } catch (\Exception $e) {
                // stay quiet - file may have been deleted previously
            }
        }
    }

    public function getUploadedFileModel($attribute) {
        $relationName = $this->_attributes[$attribute]['relationName'];
        return $this->owner->$relationName;
    }
}