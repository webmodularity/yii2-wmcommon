<?php

namespace wmc\behaviors;

use Yii;
use yii\base\Behavior;

class FindOrInsertBehavior extends Behavior
{
    public $uniqueAttributes = null;

    public function findOrInsert($runValidation = true, $attributeNames = null) {
        if ($runValidation && !$this->owner->validate($attributeNames)) {
            return false;
        }
        $existingModel = $this->getExistingModel();

        if (!is_null($existingModel)) {
            foreach ($this->owner->primaryKey() as $pk) {
                $this->owner->$pk = $existingModel->$pk;
            }
            return $this->owner->refresh();
        } else {
            return $this->owner->save(false, $attributeNames);
        }
    }

    protected function getExistingModel() {
        return $this->owner->find()->where($this->owner->getAttributes($this->uniqueAttributes, $this->owner->primaryKey()))->one();
    }
}