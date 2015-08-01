<?php

namespace wmc\behaviors;


use Yii;
use yii\base\Behavior;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class RelatedModelBehavior extends Behavior
{

    protected $_relations = [];

    protected $_linkModels = [];
    protected $_unlinkModels = [];
    protected $_gcModels = [];


    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'initAll',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    public function setRelations($relations) {
        if (!is_array($relations)) {
            throw new InvalidConfigException("Relations must be an array keyed by relation names!");
        }
        foreach ($relations as $relationName => $relationSettings) {
            if (in_array($relationName, array_keys($this->_relations))) {
                throw new InvalidConfigException("Relation names must be unique! (".$relationName.")");
            }

            // Model
            if (!isset($relationSettings['class'])) {
                throw new InvalidCallException("Relations must contain a class property!");
            } else {
                $this->_relations[$relationName] = ['class' => $relationSettings['class']];
            }

            // Init Options
            $this->_relations[$relationName]['initOptions'] = null;
            if (isset($relationSettings['initOptions']) && is_array($relationSettings['initOptions'])) {
                $this->_relations[$relationName]['initOptions'] = $relationSettings['initOptions'];
            }

            // Attributes
            $this->_relations[$relationName]['attributes'] = null;
            if (!isset($relationSettings['attributes'])) {
                $className = $relationSettings['class'];
                if (method_exists($className, 'relatedModelAttributes')) {
                    $this->_relations[$relationName]['attributes'] = $className::relatedModelAttributes();
                }
            } else if (is_array($relationName['attributes'])) {
                $this->_relations[$relationName]['attributes'] = null;
            }

            // Identifying/Mandatory
            $this->_relations[$relationName]['identifying'] = $this->_relations[$relationName]['mandatory'] = false;
            if (isset($relationSettings['identifying']) && $relationSettings['identifying'] === true) {
                $this->_relations[$relationName]['identifying'] = true;
            } else if (isset($relationSettings['mandatory']) && $relationSettings['mandatory'] === true) {
                $this->_relations[$relationName]['mandatory'] = true;
            }

            // Extra Columns
            $this->_relations[$relationName]['extraColumns'] = [];
            if (isset($relationSettings['extraColumns']) && is_array($relationSettings['extraColumns'])) {
                $this->_relations[$relationName]['extraColumns'] = $relationSettings['extraColumns'];
            }

            // deleteOnUnlink
            $this->_relations[$relationName]['deleteOnUnlink'] = true;
            if (isset($relationSettings['deleteOnUnlink']) && $relationSettings['deleteOnUnlink'] === false) {
                $this->_relations[$relationName]['deleteOnUnlink'] = false;
            }
        }
    }

    public function initAll() {
        if ($this->owner->isNewRecord) {
            foreach ($this->getRelationNames() as $relationName) {
                $initModel = $this->getNewModel($relationName);
                if ($initModel instanceof \yii\db\ActiveRecord) {
                    $this->owner->populateRelation($relationName, $initModel);
                }
            }
        }
    }

    public function loadAll($data, $formName = null) {
        foreach ($this->getRelationNames() as $relationName) {
            $relatedModel = $this->owner->getRelatedRecords()[$relationName];
            $methodName = !is_null($relatedModel->getBehavior('relatedModel')) ? 'loadAll' : 'load';
            if (!$this->owner->$relationName->$methodName($data, $formName)) {
                return false;
            }
        }
        return $this->owner->load($data, $formName);
    }

    public function validateAll($attributeNames = null, $clearErrors = true) {
        foreach ($this->getRelationNames() as $relationName) {
            $methodName = !is_null($this->owner->$relationName->getBehavior('relatedModel')) ? 'validateAll' : 'validate';
            if (!$this->owner->$relationName->$methodName($this->getRelationProperty($relationName, 'attributes'), $clearErrors)) {
                return false;
            }
        }

        return $this->owner->validate($attributeNames, $clearErrors);
    }

    public function saveAll($runValidation = true, $attributeNames = null) {
        if ($runValidation && !$this->validateAll($attributeNames)) {
            return false;
        }
        // Save Related
        foreach ($this->getRelationNames() as $relationName) {
            if (!$this->saveRelated($relationName)) {
                return false;
            }
        }

        if ($this->owner->isNewRecord && $this->hasFindOrInsert($this->owner)) {
            return $this->owner->findOrInsert(false, $attributeNames);
        } else {
            return $this->owner->save(false, $attributeNames);
        }
    }

    protected function saveRelated($relationName) {
        if (empty($this->owner->$relationName)) {
            return true;
        }
        $isEmpty = $this->owner->$relationName->isEmpty;
        $insert = $this->owner->isNewRecord;
        $methodName = !is_null($this->owner->$relationName->getBehavior('relatedModel')) ? 'saveAll' : 'save';

        if ($insert) {
            // Insert
            if ($isEmpty) {
                if ($this->isMandatory($relationName)) {
                // Trying to save an empty model that is mandatory
                    return false;
                } else {
                    // Empty model but not mandatory, no action necessary
                    return true;
                }
            } else {
                // Check if related model already exists
                if ($this->hasFindOrInsert($this->owner->$relationName)) {
                    $existingModel = $this->getExistingModel($relationName);
                    if (!is_null($existingModel)) {
                        $this->owner->populateRelation($relationName, $existingModel);
                    } else {
                        if (!$this->owner->$relationName->$methodName(false)) {
                            return false;
                        }
                    }

                    $this->link($relationName);
                    return true;
                } else {
                    return $this->owner->$relationName->$methodName(false);
                }
            }
        } else {
            // Update
            if ($isEmpty) {
                if ($this->isMandatory($relationName)) {
                    // Trying to save an empty model that is mandatory
                    return false;
                } else if (!empty($this->owner->$relationName->getDirtyAttributes($this->getRelationProperty($relationName, 'attributes')))) {
                    // Related Model is now empty
                    $this->unlink($relationName);
                    return true;
                } else {
                    // Relation was empty and it still is
                    return $this->owner->$relationName->$methodName(false);
                }
            } else {
                if (!empty($this->owner->$relationName->getDirtyAttributes($this->getRelationProperty($relationName, 'attributes'))) && $this->hasFindOrInsert($this->owner->$relationName)) {
                    /** Related Model has changed
                     * - create new Model
                     * - unlink old Model (with GC)
                     * - populate relation with new Model
                     * - link new Model
                     **/
                    $modelClass = get_class($this->owner->$relationName);
                    $newModel = new $modelClass;
                    $newModel->setAttributes($this->owner->$relationName->getAttributes($this->getRelationProperty($relationName, 'attributes')));

                    if ($existingNewModel = $modelClass::findOne($newModel->getAttributes($this->getRelationProperty($relationName, 'attributes'), $this->owner->$relationName->primaryKey()))) {
                        $newModel = $existingNewModel;
                    } else if (!$newModel->$methodName(false)) {
                        return false;
                    }
                    $this->unlink($relationName);
                    $this->owner->populateRelation($relationName, $newModel);
                    $this->link($relationName);
                    return true;
                } else {
                    return $this->owner->$relationName->$methodName(false);
                }
            }
        }
    }

    public function afterSave() {
        // Link Junction Tables
        foreach ($this->_linkModels as $linkRelationName) {
            $this->owner->link($linkRelationName, $this->owner->$linkRelationName, $this->getRelationProperty($linkRelationName, 'extraColumns'));
        }
        // Unlink Models
        foreach ($this->_unlinkModels as $model) {
            $this->tryDelete($model);
        }
    }

    public function afterFind() {
        foreach ($this->getRelationNames() as $relationName) {
            if (empty($this->owner->$relationName) && !$this->isIdentifying($relationName)) {
                $initModel = $this->getNewModel($relationName);
                if ($initModel instanceof \yii\db\ActiveRecord) {
                    $this->owner->populateRelation($relationName, $initModel);
                }
            }
            // Scenario
            $initOptions = $this->getRelationProperty($relationName, 'initOptions');
            if (!empty($initOptions)) {
                if (isset($initOptions['scenario'])) {
                    $this->owner->$relationName->setScenario($initOptions['scenario']);
                }
            }
        }
    }

    public function beforeDelete() {
        foreach ($this->getRelationNames() as $relationName) {
            if (!empty($this->owner->$relationName)) {
                $this->_gcModels[] = $this->owner->$relationName;
            }
        }
    }

    public function afterDelete() {
        foreach ($this->_gcModels as $model) {
            $this->tryDelete($model);
        }
    }

    protected function link($relationName) {
        if (!$this->hasVia($relationName)) {
            $link = $this->owner->getRelation($relationName)->link;
            foreach ($this->owner->$relationName->primaryKey() as $pk) {
                $this->owner->setAttribute($link[$pk], $this->owner->$relationName->$pk);
            }
        } else {
            $this->_linkModels[] = $relationName;
        }
    }

    protected function unlink($relationName) {
        $this->_unlinkModels[] = $this->getOldModel($relationName);
        if (!$this->hasVia($relationName)) {
            $link = $this->owner->getRelation($relationName)->link;
            foreach ($this->owner->$relationName->primaryKey() as $pk) {
                $this->owner->setAttribute($link[$pk], NULL);
            }
        } else {
            $this->owner->unlink($relationName, $this->owner->$relationName, $this->getRelationProperty($relationName, 'deleteOnUnlink'));
        }
    }

    protected function hasVia($relationName) {
        return !is_null($this->owner->getRelation($relationName)->via) ? true : false;
    }

    protected function hasFindOrInsert($model) {
        return empty($model) || empty($model->getBehavior('findOrInsert')) ? false : true;
    }

    protected function isIdentifying($relationName) {
        return $this->getRelationProperty($relationName, 'identifying') === true ? true : false;
    }

    protected function isMandatory($relationName) {
        return $this->isIdentifying($relationName) || $this->getRelationProperty($relationName, 'mandatory') === true ? true : false;
    }

    protected function getRelationNames() {
        return array_keys($this->_relations);
    }

    protected function getRelationProperty($relationName, $propertyName) {
        return $this->_relations[$relationName][$propertyName];
    }

    protected function getExistingModel($relationName) {
        return $this->owner->$relationName->find()->where($this->owner->$relationName->getAttributes($this->getRelationProperty($relationName, 'attributes'), $this->owner->primaryKey()))->one();
    }

    protected function tryDelete($model) {
        // Quietly try and delete old record
        try {
            if (!empty($model)) {
                $model->delete();
            }
        } catch (\Exception $e) {

        }
    }

    protected function getOldModel($relationName) {
        $oldClass = get_class($this->owner->$relationName);
        $pk = $this->owner->$relationName->getPrimaryKey(true);
        return $oldClass::find()->where($pk)->one();
    }

    protected function getNewModel($relationName) {
        $options = !empty($this->getRelationProperty($relationName, 'initOptions'))
            ? ArrayHelper::merge($this->getRelationProperty($relationName, 'initOptions'), ['class' => $this->getRelationProperty($relationName, 'class')])
            : ['class' => $this->getRelationProperty($relationName, 'class')];
        return Yii::createObject($options);
    }
}