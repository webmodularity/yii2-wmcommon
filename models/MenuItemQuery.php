<?php

namespace wmc\models;

use creocoder\nestedsets\NestedSetsQueryBehavior;

class MenuItemQuery extends \yii\db\ActiveQuery
{
    public function behaviors() {
        return [
            NestedSetsQueryBehavior::className(),
        ];
    }
}