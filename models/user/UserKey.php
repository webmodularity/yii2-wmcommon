<?php

namespace wmc\models\user;

use Yii;
use wmc\behaviors\TimestampBehavior;
use yii\db\IntegrityException;

/**
 * This is the model class for table "user_key".
 *
 * @property integer $id
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
    const TYPE_AUTH = 1;
    const TYPE_RESET_PASSWORD = 2;
    const TYPE_CONFIRM_EMAIL = 3;

    public static $expireIntervals = [
        'garbage' => "P30D",
        self::TYPE_RESET_PASSWORD => 'P1D',
        self::TYPE_CONFIRM_EMAIL => 'P7D'
    ];

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
        return '{{%user_key}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'user_id', 'user_key'], 'required'],
            [['type', 'user_id'], 'integer'],
            [['user_key'], 'match', 'pattern' => '/^[A-Za-z0-9_-]{32}$/']
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
            'type' => 'Type',
            'user_key' => 'Key',
            'created_at' => 'Created At',
            'expire' => 'Expire',
        ];
    }

    public function beforeDelete() {
        if (parent::beforeDelete()) {
            UserLog::add(UserLog::ACTION_USER_KEY, UserLog::RESULT_DELETED, $this->user_id,
                "Type: ".$this->type.", Key: ".$this->user_key."");
            return true;
        } else {
            return false;
        }
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
             if ($insert === true) {
                 // Check for (and remove) an expired key of this type
                 $expiredUserKey = static::find()->where(['user_id' => $this->user_id, 'type' => $this->type])->one();
                 if (!is_null($expiredUserKey)) {
                     UserLog::add(UserLog::ACTION_USER_KEY, UserLog::RESULT_EXPIRED, $expiredUserKey->user_id, $expiredUserKey->expire);
                     $expiredUserKey->delete();
                 }
                 $expireInterval = static::getExpireInterval($this->type);
                 if (!empty($expireInterval)) {
                     $date = new \DateTime();
                     $date->add(new \DateInterval($expireInterval));
                     $expire = $date->format('Y-m-d H:i:s');
                 } else {
                     $expire = null;
                 }
                 $this->expire = $expire;
             }
            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes) {
        // Garbage Collection
        $date = new \DateTime();
        $date->sub(new \DateInterval(static::getExpireInterval()));
        $garbageDate = static::getMysqlDatetime($date);
        $expiredKeys = static::find()->where('expire IS NOT NULL AND expire <= :expire', [':expire' => $garbageDate])->all();

        $deleteIds = [];
        foreach ($expiredKeys as $expiredKey) {
            UserLog::add(UserLog::ACTION_USER_KEY, UserLog::RESULT_EXPIRED, $expiredKey->user_id, $expiredKey->expire);
            $deleteIds[] = $expiredKey->id;
        }
        static::deleteAll(['id' => $deleteIds]);

        parent::afterSave($insert, $changedAttributes);
    }

    public static function generateKey() {
        return Yii::$app->security->generateRandomString();
    }

    public static function getExpireInterval($type = 'garbage') {
        if (isset(static::$expireIntervals[$type])) {
            return static::$expireIntervals[$type];
        } else {
            return null;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}