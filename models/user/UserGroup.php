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

    /**
     * All groups that are accessible by specified userGroupId (inclusive)
     * @param int $userGroupId ID of current user group
     * @param array $excludeGroupIds group id's to ignore
     * @return array id->name of groups
     */

    public static function getAccessibleGroupList($userGroupId = null, $excludeGroupIds = []) {
        if (is_null($userGroupId)) {
            $userGroupId = static::getCurrentUserGroupId();
        }
        return ArrayHelper::map(static::find()->groupsAccessible($userGroupId)->groupsExclude($excludeGroupIds)->orderBy(['id' => SORT_ASC])->all(), 'id', 'name');
    }

    /**
     * All groups that are NOT accessible by specified userGroupId
     * @param int $userGroupId ID of current user group
     * @param array $excludeGroupIds group id's to ignore
     * @return array id->name of groups
     */

    public static function getNonAccessibleGroupList($userGroupId = null, $excludeGroupIds = []) {
        if (is_null($userGroupId)) {
            $userGroupId = static::getCurrentUserGroupId();
        }
        return ArrayHelper::map(static::find()->groupsNonAccessible($userGroupId)->groupsExclude($excludeGroupIds)->orderBy(['id' => SORT_ASC])->all(), 'id', 'name');
    }

    /**
     * All groups
     * @param array $excludeGroupIds group id's to ignore
     * @return array id->name of all groups
     */

    public static function getGroupList($excludeGroupIds = []) {
        return ArrayHelper::map(static::find()->groupsExclude($excludeGroupIds)->orderBy(['id' => SORT_ASC])->all(), 'id', 'name');
    }

    public static function getCurrentUserGroupId() {
        return Yii::$app->user->identity->group_id;
    }

}