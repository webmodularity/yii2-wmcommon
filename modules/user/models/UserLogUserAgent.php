<?php

namespace wmu\models;

use Yii;

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
            [['user_agent'], 'truncate', 'length' => 255]
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

    /**
     * Returns ID of userAgent or null if record could not be located (or added).
     * @param string|null|bool $userAgent The userAgent string, null uaerAgent will result in null return,
     * a false (default) will pull the userAgent string from the request
     * @return int|null The ID of userAgent if found or null if cannot be determined
     */

    public static function generateId($userAgent = false) {
        if ($userAgent === false) {
            $userAgent = Yii::$app->request->userAgent;
        }
        if (!is_null($userAgent)) {
            $userAgentModel = static::findOneOrInsert(['user_agent' => $userAgent]);
            if (!is_null($userAgentModel)) {
                return $userAgentModel->id;
            }
        }
        return null;
    }
}