<?php

namespace wmc\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use wmc\models\UserLogUserAgent;

/**
 * This is the model class for table "user_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $action_type
 * @property integer $user_agent_id
 * @property string $session_id
 * @property integer $backend
 * @property string $ip
 * @property string $created_at
 *
 * @property User $user
 * @property Session $session
 * @property UserLogUserAgent $userAgent
 */
class UserLog extends \wmc\db\ActiveRecord
{
    const DELETE = 1;
    const CREATE = 2;
    const LOGIN = 3;
    const LOGOUT = 4;
    const RESET_PASSWORD_REQUEST = 5;
    const RESET_PASSWORD_SUCCESS = 6;
    const RESET_PASSWORD_EXPIRED = 7;

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => false
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['id', 'user_id', 'action_type'], 'required'],
            [['id', 'user_id', 'action_type', 'backend'], 'integer'],
            [['created_at'], 'safe'],
            [['ip'], 'string', 'max' => 16],
            [['session_id'], 'string', 'max' => 64],
            [['session_id'], 'unique']
            */
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'action_type' => 'Action Type',
            'user_agent_id' => 'User Agent',
            'session_id' => 'Session ID',
            'backend' => 'Is Backend?',
            'ip' => 'IP',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['person_id' => 'user_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSession()
    {
        return $this->hasOne(Session::className(), ['id' => 'session_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAgent()
    {
        return $this->hasOne(UserLogUserAgent::className(), ['id' => 'user_agent_id']);
    }

    public static function add($actionTypeId, $userId = null) {
        $userId = is_null($userId) ? Yii::$app->user->id : $userId;
        $backend = Yii::$app instanceof \wma\web\Application ? 1 : 0;
        if ($actionTypeId == self::RESET_PASSWORD_EXPIRED) {
            $ip = NULL;
            $sessionId = NULL;
            $userAgentId = NULL;
        } else {
            $ip = inet_pton(Yii::$app->request->getUserIP());
            $sessionId = Yii::$app->session->getId();
            $userAgentId = UserLogUserAgent::getUserAgentId(Yii::$app->request->getUserAgent());
        }
        $userLog = new UserLog();
        $userLog->user_id = $userId;
        $userLog->action_type = $actionTypeId;
        $userLog->user_agent_id = $userAgentId;
        $userLog->ip = $ip;
        $userLog->session_id = $sessionId;
        $userLog->backend = $backend;
        $userLog->save();
    }
}