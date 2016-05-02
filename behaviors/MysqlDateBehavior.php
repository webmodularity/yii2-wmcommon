<?php

namespace wmc\behaviors;

use Yii;
use wmc\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;

class MysqlDateBehavior extends AttributeBehavior
{
    public $insertFormat = 'Y-m-d';

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind'
        ];
    }

    public function beforeSave($event) {
        foreach ($this->attributes as $attribute) {
            // Convert date to MySQL friendly
            $date = new \DateTime($this->owner->$attribute);
            $this->owner->$attribute = $date->format($this->insertFormat);
        }
    }

    public function afterFind() {
        foreach ($this->attributes as $attribute) {
            // Convert date to readable format
            $this->owner->$attribute = Yii::$app->formatter->asDate($this->owner->$attribute);
        }
    }

}