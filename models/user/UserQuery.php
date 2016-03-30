<?php

namespace wmc\models\user;

use wmc\models\user\User;

/**
 * This is the ActiveQuery class for [[User]].
 *
 * @see User
 */
class UserQuery extends \yii\db\ActiveQuery
{
    public function active() {
        $this->andWhere(['status' => User::STATUS_ACTIVE]);
        return $this;
    }

    public function inactive() {
        $this->andWhere(['or', ['status' => User::STATUS_NEW], ['status' => User::STATUS_DELETED]]);
        return $this;
    }

    public function deleted() {
        $this->andWhere(['status' => User::STATUS_DELETED]);
        return $this;
    }

    public function notDeleted() {
        $this->andWhere(['or', ['status' => User::STATUS_NEW], ['status' => User::STATUS_ACTIVE]]);
        return $this;
    }

    public function pending() {
        $this->andWhere(['status' => User::STATUS_NEW]);
        return $this;
    }

    public function superAdmin($bool = true) {
        if ($bool === false){
            $this->andWhere(['!=', 'group_id', UserGroup::SU]);
        } else {
            $this->andWhere(['group_id' => UserGroup::SU]);
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @return User[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return User|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
} 