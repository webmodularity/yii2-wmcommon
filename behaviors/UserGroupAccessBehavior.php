<?php

namespace wmc\behaviors;

use yii\base\Behavior;
use wmc\models\user\UserGroup;

class UserGroupAccessBehavior extends Behavior
{
    //public $userGroupAccess = [];

    public $viaTableName = '';
    public $userGroupIdField = 'user_group_id';
    public $itemIdField = '';

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserGroups() {
        return $this->owner->hasMany(UserGroup::className(), ['id' => $this->userGroupIdField])->viaTable($this->viaTableName, [$this->itemIdField => 'id']);
    }

    public function groupHasAccess($groupId = UserGroup::GUEST) {
        if (is_int($groupId) && $groupId >= 0) {
            return $this->getUserGroups()->where(['id' => $groupId])->exists();
        }
        return false;
    }

    public function saveUserGroups() {

    }

}