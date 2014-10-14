<?php

namespace wmc\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $action_type
 * @property integer $backend
 * @property string $created_at
 * @property string $ip
 * @property string $session_id
 *
 * @property User $user
 * @property Session $session
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

    //public $backend = 0;

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
            'backend' => 'Is Backend',
            'created_at' => 'Created At',
            'ip' => 'IP Address',
            'session_id' => 'Session ID',
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

    public static function add($actionTypeId, $userId = null) {
        if (is_null($userId)) {
            $userId = Yii::$app->user->id;
        }
        $userLog = new UserLog();
        $userLog->user_id = $userId;
        $userLog->action_type = $actionTypeId;
        $userLog->ip = inet_pton(Yii::$app->request->getUserIP());
        $userLog->session_id = Yii::$app->session->getId();
        $userLog->save();
    }
}