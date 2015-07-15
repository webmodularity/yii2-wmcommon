<?php

namespace wmc\behaviors;

use yii\base\Behavior;

class RelatedModelBehavior extends Behavior
{

    public $_relations = [];

    public function setRelations($config) {
        if (is_string($config)) {
            $this->_relations = [$config => []];
        } else if (is_array($config)) {
            $this->_relations = $config;
        }
    }

    public function initAll() {
        foreach ($this->getRelationNames() as $relationName) {
            $initModel = $this->getRelationProperty($relationName, 'init');
            if ($initModel instanceof \yii\db\ActiveRecord) {
                $this->owner->populateRelation($relationName, $initModel);
            }
        }
    }

    public function loadAll($data, $formName = null) {
        foreach ($this->getRelationNames() as $relationName) {
            if (!$this->owner->$relationName->load($data, $formName)) {
                return false;
            }
        }

        return $this->owner->load($data, $formName);
    }

    public function saveAll($runValidation = true, $attributeNames = null) {

    }

    protected function getRelationNames() {
        return array_keys($this->_relations);
    }

    protected function getRelationProperty($relationName, $propertyName) {
        return isset($this->_relations[$relationName]) && isset($this->_relations[$relationName][$propertyName])
            ? $this->_relations[$relationName][$propertyName] : null;
    }
}