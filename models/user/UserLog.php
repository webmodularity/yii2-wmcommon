<?php

namespace wmc\models\user;

use Yii;
use wmc\behaviors\TimestampBehavior;
use wmc\helpers\IPHelper;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "user_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $app
 * @property integer $action_type
 * @property integer $result_type
 * @property string $data
 * @property integer $path_id
 * @property integer $user_agent_id
 * @property string $session_id
 * @property string $ip
 * @property string $created_at
 *
 * @property User $user
 * @property Session $session
 * @property UserLogUserAgent $userAgent
 * @property UserLogPath $path
 */
class UserLog extends \wmc\db\ActiveRecord
{
    const APP_FRONTEND = 1;
    const APP_BACKEND = 2;
    const APP_CONSOLE = 3;

    const ACTION_DELETE = 1;
    const ACTION_CREATE = 2;
    const ACTION_LOGIN = 3;
    const ACTION_LOGOUT = 4;
    const ACTION_RESET_PASSWORD = 5;
    const ACTION_USER_KEY = 6;
    const ACTION_ACCESS = 7;
    const ACTION_UPDATE = 8;
    const ACTION_EMAIL = 9;
    const ACTION_CHANGE_PASSWORD = 10;
    const ACTION_CHANGE_EMAIL = 11;

    const RESULT_SUCCESS = 1;
    const RESULT_FAIL = 2;
    const RESULT_REQUEST = 3;
    const RESULT_EXPIRED = 4;
    const RESULT_DELETED = 5;
    const RESULT_NEW = 6;
    const RESULT_COOLDOWN = 7;

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
        return '{{%user_log}}';
    }

    /**
     * @inheritdoc
     * @return UserLogQuery
     */
    public static function find() {
        return new UserLogQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'app' => 'System',
            'action_type' => 'Action',
            'result_type' => 'Result',
            'data' => 'Data',
            'path_id' => 'Path',
            'user_agent_id' => 'User Agent',
            'session_id' => 'Session ID',
            'ip' => 'IP Address',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPath()
    {
        return $this->hasOne(UserLogPath::className(), ['id' => 'path_id']);
    }

    public static function add($actionType, $resultType, $userId = null, $data = null) {
        $userId = is_null($userId) ? Yii::$app->user->id : $userId;
        $userLog = new UserLog();
        $userLog->user_id = $userId;
        $userLog->action_type = $actionType;
        $userLog->result_type = $resultType;
        $userLog->data = $data;
        $userLog->save(false);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert === true) {
                $path = new UserLogPath(['path' => Yii::$app->request->pathInfo]);
                if (!$path->findOrInsert()) {
                    return false;
                }
                $this->app = Yii::$app instanceof \wma\web\Application ? self::APP_BACKEND : self::APP_FRONTEND;
                $this->path_id = ArrayHelper::getValue($path, 'id');
                if ($this->captureUserInfo() === true) {
                    $userAgent = new UserLogUserAgent(['user_agent' => Yii::$app->request->userAgent]);
                    if (!$userAgent->findOrInsert()) {
                        return false;
                    }
                    $session = Yii::$app->session;
                    $session->open();
                    $this->ip = IPHelper::toBinaryIp(Yii::$app->request->getUserIP());
                    $this->session_id = $session->getId();
                    $this->user_agent_id = ArrayHelper::getValue($userAgent, 'id');
                }
            }
            return true;
        } else {
            return false;
        }
    }

    protected function captureUserInfo() {
        $noUserCapture = [
            [self::ACTION_RESET_PASSWORD, self::RESULT_EXPIRED]
        ];
        if (in_array([$this->action_type, $this->result_type], $noUserCapture)) {
            return false;
        } else {
            return true;
        }
    }

    public static function getAppList() {
        return [
            static::APP_FRONTEND => 'Site',
            static::APP_BACKEND => 'CMS',
            static::APP_CONSOLE => 'CLI'
        ];
    }

    public static function getActionList() {
        return [
            static::ACTION_LOGIN => "Login",
            static::ACTION_LOGOUT => "Logout",
            static::ACTION_CREATE => "Create",
            static::ACTION_DELETE => "Delete",
            static::ACTION_UPDATE => "Update",
            static::ACTION_RESET_PASSWORD => "Password Reset",
            static::ACTION_USER_KEY => "User Key",
            static::ACTION_ACCESS => "Access",
            static::ACTION_EMAIL => "Email",
            static::ACTION_CHANGE_PASSWORD => "Change Password",
            static::ACTION_CHANGE_EMAIL => "Change Email"

        ];
    }

    public static function getResultList() {
        return [
            static::RESULT_SUCCESS => "Success",
            static::RESULT_FAIL => "Fail",
            static::RESULT_COOLDOWN => "Cooldown",
            static::RESULT_DELETED => "Deleted",
            static::RESULT_EXPIRED => "Expired",
            static::RESULT_NEW => "New",
            static::RESULT_REQUEST => "Request"
        ];
    }

    public static function getIntervalList() {
        return [
            'PT10M' => "10 Minutes",
            'PT1H' => "1 Hour",
            'PT12H' => "12 Hours",
            'P1D' => "Day",
            'P7D' => "Week",
            'P1M' => "Month",
            'P1Y' => "Year"
        ];
    }
}