<?php

namespace wmc\models\user;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%user_group}}".
 *
 * @property integer $id
 * @property string $name
 *
 * @property User[] $users
 */
class UserGroup extends \wmc\db\ActiveRecord
{
    const GUEST = 0;
    const USER = 1;
    const AUTHOR = 20;
    const ADMIN = 100;
    const SU = 255;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_group}}';
    }

    /**
     * @inheritdoc
     * @return UserGroupQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserGroupQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'name'], 'required'],
            [['id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers() {
        return $this->hasMany(User::className(), ['group_id' => 'id']);
    }

    public static function getUserGroupList($currentUserGroupId = false, $includeGuest = false)
    {
        if ($includeGuest) {
            return ArrayHelper::map(static::find()->userGroupFilter($currentUserGroupId)->orderBy(['id' => SORT_ASC])->all(), 'id', 'name');
        } else {
            return ArrayHelper::map(static::find()->guest(false)->userGroupFilter($currentUserGroupId)->orderBy(['id' => SORT_ASC])->all(), 'id', 'name');
        }
    }
}