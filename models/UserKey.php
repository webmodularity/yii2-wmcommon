<?php

namespace wmc\models;

use Yii;
use wmc\models\User;
use wmc\models\UserKeyLog;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_key".
 *
 * @property integer $user_id
 * @property integer $type
 * @property string $user_key
 * @property string $created_at
 * @property string $expire_time
 *
 * @property User $user
 */
class UserKey extends \wmc\models\ActiveRecord
{
    const TYPE_RESET_PASSWORD = 1;
    const EXPIRE_RESET_PASSWORD = 'P1D';
    const TYPE_CONFIRM_EMAIL = 2;
    const EXPIRE_CONFIRM_EMAIL = 'P7D';

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
        return 'user_key';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['user_id', 'type', 'key', 'create_time', 'expire_time'], 'required'],
            [['user_id', 'type'], 'integer'],
            [['create_time', 'expire_time'], 'safe'],
            [['key'], 'string', 'max' => 32],
            [['key'], 'unique']
            */
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'type' => 'Type',
            'user_key' => 'Key',
            'created_at' => 'Create Time',
            'expire_time' => 'Expire Time',
        ];
    }

    public static function getKey($userId, $type = 'reset-password') {
        self::expiredGarbageCollection();
        $key = self::findOne(['user_id' => $userId, 'type' => self::getTypeIdFromType($type)]);
        if (is_null($key)) {
            $date = new \DateTime();
            $date->add(new \DateInterval(self::getExpireFromType($type)));
            $expireTime = Yii::$app->formatter->asMysqlDatetime($date);
            $key = new UserKey();
            $key->user_id = $userId;
            $key->type = self::getTypeIdFromType($type);
            $key->user_key = Yii::$app->security->generateRandomString();
            $key->expire_time = $expireTime;
            $key->save(false);

            // Log add
            $log = new UserKeyLog();
            $log->user_id = $key->user_id;
            $log->key_type = $key->type;
            $log->action_type = UserKeyLog::ADD;
            $log->ip = inet_pton(Yii::$app->request->getUserIP());
            $log->save(false);
        }
        return $key;
    }

    public static function expiredGarbageCollection() {
        $expiredKeys = self::find()->where(
            'expire_time <= :expire_time',
            [
                ':expire_time' => Yii::$app->formatter->asMysqlDatetime()
            ]
        )->all();

        foreach ($expiredKeys as $expiredKey) {
            $log = new UserKeyLog([
                    'user_id' => $expiredKey->user_id,
                    'key_type' => $expiredKey->type,
                    'action_type' => UserKeyLog::EXPIRED,
                    'ip' => NULL
                ]);
            $log->save(false);
            $expiredKey->delete();
        }
    }

    public static function getTypeIdFromType($typeName = 'reset-password') {
        return constant('self::TYPE_' . self::normalizeTypeName($typeName));
    }

    public static function getExpireFromType($typeName = 'reset-password') {
        return constant('self::EXPIRE_' . self::normalizeTypeName($typeName));;
    }

    public static function normalizeTypeName($typeName = 'reset-password') {
        return strtoupper(str_replace('-', '_', $typeName));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['person_id' => 'user_id']);
    }
}