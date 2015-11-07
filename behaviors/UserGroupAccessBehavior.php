<?php

namespace wmc\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use wmc\models\user\UserGroup;
use yii\validators\Validator;

class UserGroupAccessBehavior extends Behavior
{
    public $userGroupIds = [];

    public $viaTableName = '';
    public $userGroupIdField = 'user_group_id';
    public $itemIdField = '';

    public function attach($owner) {
        parent::attach($owner);

        // Append some validators
        $validators = $owner->validators;
        $validators->append(Validator::createValidator('safe', $owner, ['userGroupIds']));
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind'
        ];
    }

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

    public function afterInsert($event) {
        // Link UserGroups
        foreach ($this->userGroupIds as $userGroupId) {
            $userGroup = UserGroup::findOne($userGroupId);
            if (!is_null($userGroup)) {
                $this->owner->link('userGroups', $userGroup);
            }
        }
    }

    public function afterUpdate($event) {
        $currentUserGroupIdMap = ArrayHelper::getColumn($this->owner->userGroups, 'id');
        $linkIds = array_diff($this->userGroupIds, $currentUserGroupIdMap);
        $unlinkIds = array_diff($currentUserGroupIdMap, $this->userGroupIds);

        foreach ($linkIds as $linkId) {
            $userGroupAdd = UserGroup::findOne($linkId);
            if (!is_null($userGroupAdd)) {
                $this->owner->link('userGroups', $userGroupAdd);
            }
        }
        foreach ($unlinkIds as $unlinkId) {
            $userGroupRemove = UserGroup::findOne($unlinkId);
            if (!is_null($userGroupRemove)) {
                $this->owner->unlink('userGroups', $userGroupRemove, true);
            }
        }
    }

    public function afterFind($event) {
        $this->owner->userGroupIds = ArrayHelper::getColumn($this->owner->userGroups, 'id');
    }

}