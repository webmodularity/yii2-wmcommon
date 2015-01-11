<?php

namespace wmu\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_cooldown_log".
 *
 * @property integer $id
 * @property integer $action_type
 * @property integer $path_id
 * @property integer $user_agent_id
 * @property string $ip
 * @property string $request_method
 * @property string $created_at
 *
 * @property UserCooldownLogPath $path
 * @property UserLogUserAgent $userAgent
 */
class UserCooldownLog extends \wmc\db\ActiveRecord
{
    const ACTION_LOGIN_USER = 1;
    const ACTION_LOGIN_PASS = 2;
    const ACTION_RESET_PASSWORD_USER = 3;
    const ACTION_RESET_PASSWORD_EMAIL = 4;
    const ACTION_CONFIRM_EMAIL_EXPIRED_KEY = 5;
    const ACTION_CONFIRM_EMAIL_BAD_KEY = 6;

    public static $cooldownInterval = "PT5M";
    public static $cooldownThreshold = 10;
    public static $validRequestMethods = ['GET','POST','HEAD','PUT','PATCH','DELETE'];

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
        return 'user_cooldown_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['action_type', 'path_id'], 'required'],
            [['action_type', 'path_id', 'user_agent_id'], 'integer'],
            [['request_method'], 'string'],
            [['created_at'], 'safe'],
            [['ip'], 'string', 'max' => 16]
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
            'path_id' => 'Path ID',
            'user_agent_id' => 'User Agent ID',
            'ip' => 'Ip',
            'request_method' => 'Request Method',
            'created_at' => 'Created At',
        ];
    }

    public static function add($actionTypeId, $resultTypeId) {
        $cooldownLog = new UserCooldownLog();
        $cooldownLog->action_type = $actionTypeId;
        return $cooldownLog->save();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPath()
    {
        return $this->hasOne(UserCooldownLogPath::className(), ['id' => 'path_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAgent()
    {
        return $this->hasOne(UserLogUserAgent::className(), ['id' => 'user_agent_id']);
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($insert === true) {
                $this->path_id = UserCooldownLogPath::getId();
                $this->user_agent_id = UserLogUserAgent::getId();
                $this->ip = Yii::$app->formatter->asBinaryIP(Yii::$app->request->getUserIP());
                $this->request_method = in_array(Yii::$app->request->method, static::$validRequestMethods)
                    ? Yii::$app->request->method
                    : 'GET';
            }
            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes) {
        if ($insert === true) {
            $intervalTime = new \DateTime();
            $intervalTime->sub(new \DateInterval(static::$cooldownInterval));
            $createdAtTime = Yii::$app->formatter->asMysqlDatetime($intervalTime);
            $recordCount = static::find()->where(
                'created_at >= :createdTime AND ip = :ip',
                [
                    ':createdTime' => $createdAtTime,
                    ':ip' => $this->ip
                ]
            )->count();
            if ($recordCount >= static::$cooldownThreshold) {
                return static::findOneorInsert(['ip' => $this->ip]);
            }
        }
        parent::afterSave($insert, $changedAttributes);
    }
}