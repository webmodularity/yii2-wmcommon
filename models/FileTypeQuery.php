<?php

namespace wmc\models;

/**
 * This is the ActiveQuery class for [[Log]].
 *
 * @see Log
 */
class FileTypeQuery extends \yii\db\ActiveQuery
{

    public function includeTypes($typeIds) {
        if (!empty($typeIds)) {
            return $this->andWhere(['in', 'id', $typeIds]);
        } else {
            return $this;
        }
    }

    public function excludeTypes($typeIds) {
        if (!empty($typeIds)) {
            return $this->andWhere(['not in', 'id', $typeIds]);
        } else {
            return $this;
        }
    }

}