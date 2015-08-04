<?php

namespace wmc\models\user;

use Yii;
use wmc\behaviors\FindOrInsertBehavior;

/**
 * This is the model class for table "user_log_user_agent".
 *
 * @property integer $id
 * @property string $user_agent
 *
 * @property UserCooldownLog[] $userCooldownLogs
 * @property UserLog[] $userLogs
 */
class UserLogUserAgent extends \wmc\db\ActiveRecord
{
    public function behaviors() {
        return [
            'findOrInsert' =>
                [
                    'class' => FindOrInsertBehavior::className()
                ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_log_user_agent}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_agent'], '\wmc\validators\TruncateValidator', 'length' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_agent' => 'User Agent',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserCooldownLogs()
    {
        return $this->hasMany(UserCooldownLog::className(), ['user_agent_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserLogs()
    {
        return $this->hasMany(UserLog::className(), ['user_agent_id' => 'id']);
    }

}