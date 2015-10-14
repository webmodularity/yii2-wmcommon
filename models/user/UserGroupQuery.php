<?php

namespace wmc\models\user;

/**
 * This is the ActiveQuery class for [[UserGroup]].
 *
 * @see UserGroup
 */
class UserGroupQuery extends \yii\db\ActiveQuery
{
    /*
    public function guest($state = false) {
        $operand = $state === true ? '==' : '!=';
        $this->andWhere([$operand, 'id', UserGroup::GUEST]);
        return $this;
    }
    */

    public function userGroupFilter($userGroupId) {
        if (is_int($userGroupId) && $userGroupId >= 0) {
            $this->andWhere(['<=', 'id', $userGroupId]);
        }
        return $this;
    }

    public function excludeUserGroups($userGroupIds = [])
    {
        if (!empty($userGroupIds)) {
            $this->andWhere(['not in', 'id', $userGroupIds]);
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @return UserGroup[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserGroup|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
} 