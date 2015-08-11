<?php

namespace wmc\models\user;

use Yii;
use wmc\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use wmc\helpers\IPHelper;

/**
 * This is the model class for table "{{%user_cooldown_log}}".
 *
 * @property integer $id
 * @property integer $action_type
 * @property integer $result_type
 * @property integer $path_id
 * @property integer $user_agent_id
 * @property string $session_id
 * @property string $ip
 * @property string $request_method
 * @property string $created_at
 *
 * @property UserLogPath $path
 * @property UserLogUserAgent $userAgent
 * @property Session $session
 */
class UserCooldownLog extends \wmc\db\ActiveRecord
{
    const ACTION_LOGIN = 1;
    const ACTION_RESET_PASSWORD = 2;
    const ACTION_CONFIRM_EMAIL = 3;
    const ACTION_ACCESS = 4;
    const ACTION_CHANGE_PASSWORD = 5;
    const ACTION_CHANGE_EMAIL = 6;

    const RESULT_NO_RECORD = 1;
    const RESULT_NEW = 2;
    const RESULT_EXPIRED = 3;
    const RESULT_DELETED = 4;
    const RESULT_BAD_PASSWORD = 5;
    const RESULT_FAIL = 6;
    const RESULT_COOLDOWN = 7;

    public static $cooldownInterval = "PT2M";
    public static $cooldownThreshold = 10;
    public static $validRequestMethods = ['GET','POST','HEAD','PUT','PATCH','DELETE'];

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => false,
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_cooldown_log}}';
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
            'result_type' => 'Result Type',
            'path_id' => 'Path ID',
            'user_agent_id' => 'User Agent ID',
            'session_id' => 'Session ID',
            'ip' => 'IP',
            'request_method' => 'Request Method',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPath()
    {
        return $this->hasOne(UserLogPath::className(), ['id' => 'path_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserAgent()
    {
        return $this->hasOne(UserLogUserAgent::className(), ['id' => 'user_agent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSession()
    {
        return $this->hasOne(Session::className(), ['id' => 'session_id']);
    }

    public static function add($actionTypeId, $resultTypeId) {
        $cooldownLog = new UserCooldownLog();
        $cooldownLog->action_type = $actionTypeId;
        $cooldownLog->result_type = $resultTypeId;
        return $cooldownLog->save();
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($insert === true) {
                $path = new UserLogPath(['path' => Yii::$app->request->pathInfo]);
                if (!$path->findOrInsert()) {
                    return false;
                }
                $this->path_id = ArrayHelper::getValue($path, 'id');
                $userAgent = new UserLogUserAgent(['user_agent' => Yii::$app->request->userAgent]);
                if (!$userAgent->findOrInsert()) {
                    return false;
                }
                $this->user_agent_id = ArrayHelper::getValue($userAgent, 'id');
                $session = Yii::$app->session;
                $session->open();
                $this->ip = IPHelper::toBinaryIp(Yii::$app->request->getUserIP());
                $this->session_id = $session->getId();
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
        if (static::getCooldownCount($this->ip) >= static::$cooldownThreshold) {
            // Add User to cooldown
            $uc = UserCooldown::findOne(['ip' => $this->ip]);
            if (empty($uc)) {
                $uc = new UserCooldown();
                $uc->ip = $this->ip;
            }
            $uc->save(false);
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Number of cooldowns this IP address has in self::cooldownInterval
     *
     * @param $ip Binary formatted IP address to check cooldown count on
     * @return integer How many cooldowns this ip has
     */

    public static function getCooldownCount($ip = null) {
        if (is_null($ip)) {
            $ip = IPHelper::toBinaryIp(Yii::$app->request->getUserIP());
        }
        $intervalTime = new \DateTime(NULL, new \DateTimeZone("UTC"));
        $intervalTime->sub(new \DateInterval(static::$cooldownInterval));
        $createdAtTime = static::getMysqlDatetime($intervalTime);
        return static::find()->where(
            'created_at >= :createdTime AND ip = :ip',
            [
                ':createdTime' => $createdAtTime,
                ':ip' => $ip
            ]
        )->count();
    }
}