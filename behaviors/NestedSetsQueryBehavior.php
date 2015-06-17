<?php

namespace wmc\behaviors;

class NestedSetsQueryBehavior extends \creocoder\nestedsets\NestedSetsQueryBehavior
{
    /**
     * Gets the root nodes. Modified to drop addOrderBy
     * @return \yii\db\ActiveQuery the owner
     */
    public function roots()
    {
        $model = new $this->owner->modelClass();
        $this->owner
            ->andWhere([$model->leftAttribute => 1]);
        return $this->owner;
    }

    public function nonRoots()
    {
        $model = new $this->owner->modelClass();
        $this->owner
            ->andWhere("".$model->leftAttribute." != 1");
        return $this->owner;
    }
}