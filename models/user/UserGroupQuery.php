<?php

namespace wmc\models\user;

/**
 * This is the ActiveQuery class for [[UserGroup]].
 *
 * @see UserGroup
 */
class UserGroupQuery extends \yii\db\ActiveQuery
{
    public function groupsAccessible($groupId) {
        $this->andWhere(['<=', 'id', $groupId]);
        return $this;
    }

    public function groupsNonAccessible($groupId) {
        $this->andWhere(['>', 'id', $groupId]);
        return $this;
    }

    public function groupsExclude($groupIds = [])
    {
        if (!empty($groupIds)) {
            $this->andWhere(['not in', 'id', $groupIds]);
        }
        return $this;
    }

    public function groupsGreater($groupId) {
        $this->andWhere(['>=', 'id', $groupId]);
        return $this;
    }

    public function groupsLesser($groupId) {
        $this->andWhere(['<=', 'id', $groupId]);
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