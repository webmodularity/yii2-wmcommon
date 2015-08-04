<?php

namespace wmc\rbac;

use Yii;
use wmc\models\user\UserGroup;

/**
* Checks if user group matches
*/
class UserGroupRule extends \yii\rbac\Rule
{
    public $name = 'userGroup';

    public function execute($user, $item, $params) {
        if (!Yii::$app->user->isGuest) {
            $groupId = Yii::$app->user->identity->group_id;
            if (strtolower($item->name) === 'su') {
                return $groupId == UserGroup::SU;
            } else if ($item->name === 'admin') {
                return $groupId >= UserGroup::ADMIN;
            } else if ($item->name === 'author') {
                return $groupId >= UserGroup::AUTHOR;
            } else if ($item->name === 'user') {
                return $groupId >= UserGroup::USER;
            }
        }
    return false;
    }
}