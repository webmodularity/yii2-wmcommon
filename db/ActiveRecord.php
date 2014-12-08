<?php

namespace wmc\db;

class ActiveRecord extends \yii\db\ActiveRecord
{

    /**
     * Takes a full AR model with an unset PK and returns AR result if record is found
     * @return ActiveRecord|null null if no result found
     */

    public function findOneFromAttributes() {
        return $this->findOne($this->getAttributes(null, $this->primaryKey()));
    }

    public static function findOneOrInsert($condition) {
        if (!is_array($condition)) {
            return null;
        }
        $model = static::findOne($condition);
        if (is_null($model)) {
            $className = static::className();
            $model = new $className;
            foreach ($condition as $attribute => $val) {
                $model->$attribute = $val;
            }
            if ($model->save() === false) {
                return null;
            }
        }
        return $model;
    }

}