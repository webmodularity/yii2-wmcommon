<?php

namespace wmc\models;

use Yii;
use wmc\models\User;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_key".
 *
 * @property integer $user_id
 * @property integer $type
 * @property string $user_key
 * @property string $created_at
 * @property string $expire
 *
 * @property User $user
 */
class UserKey extends \wmc\db\ActiveRecord
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
            [['user_id', 'type', 'key', 'created_at', 'expire'], 'required'],
            [['user_id', 'type'], 'integer'],
            [['created_at', 'expire'], 'safe'],
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
            'expire' => 'Expire Time',
        ];
    }

    public static function getKey($userId, $type = 'reset-password') {
        self::expiredGarbageCollection();
        $key = self::findOne(['user_id' => $userId, 'type' => self::getTypeIdFromType($type)]);
        if (is_null($key)) {
            $date = new \DateTime();
            $date->add(new \DateInterval(self::getExpireFromType($type)));
            $expire = Yii::$app->formatter->asMysqlDatetime($date);
            $key = new UserKey();
            $key->user_id = $userId;
            $key->type = self::getTypeIdFromType($type);
            $key->user_key = Yii::$app->security->generateRandomString();
            $key->expire = $expire;
            $key->save(false);

            // Log add
            UserLog::add(UserLog::RESET_PASSWORD_REQUEST, $userId);
        }
        return $key;
    }

    public static function expiredGarbageCollection() {
        $expiredKeys = self::find()->where(
            'expire <= :expire',
            [
                ':expire' => Yii::$app->formatter->asMysqlDatetime()
            ]
        )->all();

        foreach ($expiredKeys as $expiredKey) {
            UserLog::add(UserLog::RESET_PASSWORD_EXPIRED, $expiredKey->user_id);
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