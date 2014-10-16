<?php

namespace wmc\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_log_failed".
 *
 * @property integer $id
 * @property integer $action_type
 * @property integer $reason_type
 * @property integer $user_id
 * @property string $data
 * @property string $ip
 * @property string $created_at
 *
 * @property User $user
 */
class UserLogFailed extends \wmc\db\ActiveRecord
{
    const ACTION_LOGIN = 1;
    const ACTION_RESET_PASSWORD = 2;

    const REASON_BAD_USERNAME = 1;
    const REASON_BAD_PASSWORD = 2;
    const REASON_BAD_EMAIL = 3;

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
        return 'user_log_failed';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['action_type', 'reason_id'], 'required'],
            [['action_type', 'reason_id'], 'integer'],
            [['ip'], 'safe'],
            [['username'], 'string', 'max' => 255]
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
            'action_type' => 'Action Type',
            'reason_type' => 'Reason Type',
            'user_id' => 'User ID',
            'data' => 'Data',
            'ip' => 'Ip',
            'created_at' => 'Created At',
        ];
    }

    public static function add($actionType, $reasonType, $userId = null, $data = null) {
        $log = new UserLogFailed();
        $log->action_type = $actionType;
        $log->reason_type = $reasonType;
        $log->user_id = $userId;
        $log->data = $data;
        $log->ip = inet_pton(Yii::$app->request->getUserIP());
        $log->save(false);
    }

}