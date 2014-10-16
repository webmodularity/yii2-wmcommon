<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "user_log_user_agent".
 *
 * @property integer $id
 * @property string $user_agent
 *
 * @property UserLog[] $userLogs
 */
class UserLogUserAgent extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_log_user_agent';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_agent'], 'required'],
            [['user_agent'], 'string', 'max' => 255],
            [['user_agent'], 'unique']
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
    public function getUserLogs()
    {
        return $this->hasMany(UserLog::className(), ['user_agent_id' => 'id']);
    }

    public static function getUserAgentId($userAgent) {
        if (!$userAgent) {
            return null;
        } else {
            $userAgent = mb_substr(trim($userAgent), 0, 255);
        }
        $model = self::findOne(['user_agent' => $userAgent]);
        if (is_null($model)) {
            // Add New
            $model = new UserLogUserAgent();
            $model->user_agent = $userAgent;
            if (!$model->save()) {
                return null;
            }
        }
        return $model->id;
    }
}