<?php

namespace wmu\models;

use Yii;

/**
 * This is the model class for table "user_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $app
 * @property integer $action_type
 * @property integer $action_detail_type
 * @property integer $result_type
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

    const RESULT_SUCCESS = 1;
    const RESULT_FAIL = 2;
    const RESULT_REQUEST = 3;
    const RESULT_EXPIRED = 4;

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
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'app' => 'App',
            'action_type' => 'Action Type',
            'action_detail_type' => 'Action Detail Type',
            'result_type' => 'Result Type',
            'path_id' => 'Path ID',
            'user_agent_id' => 'User Agent ID',
            'session_id' => 'Session ID',
            'ip' => 'Ip',
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPath()
    {
        return $this->hasOne(UserLogPath::className(), ['id' => 'path_id']);
    }

    public static function add($actionType, $resultType, $userId = null, $createdAt = null) {
        $userId = is_null($userId) ? Yii::$app->user->id : $userId;
        $userLog = new UserLog();
        $userLog->user_id = $userId;
        $userLog->action_type = $actionType;
        $userLog->result_type = $resultType;
        if (static::isValidDatetime($createdAt) === true) {
            $userLog->created_at = $createdAt;
        }
        $userLog->save(false);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert === true) {
                $this->app = Yii::$app instanceof \wma\web\Application ? self::APP_BACKEND : self::APP_FRONTEND;
                $this->user_agent_id = UserLogUserAgent::getId();
                if (!$this->created_at) {
                    $this->created_at = Yii::$app->formatter->asMysqlDatetime();
                }
                if ($this->captureUserInfo() === true) {
                    $this->ip = Yii::$app->formatter->asBinaryIP(Yii::$app->request->getUserIP());
                    $this->session_id = Yii::$app->session->getId();
                    $this->user_agent_id = UserLogUserAgent::getId();
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

    public static function isValidDatetime($date) {
        if (!is_string($date)) {
            return false;
        }
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $errors = DateTime::getLastErrors();
        if (!empty($errors['warning_count'])) {
            return false;
        }
        return $dateTime !== false;
    }
}