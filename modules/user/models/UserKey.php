<?php

namespace wmu\models;

use Yii;
use yii\behaviors\TimestampBehavior;

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
    const TYPE_RESET_PASSWORD = 1;
    const TYPE_CONFIRM_EMAIL = 2;

    public static $expireIntervals = [
        'garbage' => "P30D",
        self::TYPE_RESET_PASSWORD => 'P1D',
        self::TYPE_CONFIRM_EMAIL => 'P7D'
    ];

    /**
     * @var int 1-100/100 expired key cleanup
     **/
    public $gc_probability = 100;

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

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
             if ($insert === true) {
                 // Check for (and remove) an expired key of this type
                 $expiredUserKey = static::findByUser($this->user_id, $this->type, true);
                 if (!is_null($expiredUserKey)) {
                     UserLog::add(UserLog::ACTION_USER_KEY, UserLog::RESULT_EXPIRED, $expiredUserKey->user_id, $expiredUserKey->expire);
                     $expiredUserKey->delete();
                 }
                 $date = new \DateTime();
                 $date->add(new \DateInterval(static::getExpireInterval($this->type)));
                 $expire = Yii::$app->formatter->asMysqlDatetime($date);
                 $this->expire = $expire;
                 $this->user_key = Yii::$app->security->generateRandomString();
             }
            return true;
        } else {
            return false;
        }
    }

    public function afterFind() {
        // Clean up old records
        if (mt_rand(1,100) <= $this->gc_probability) {
            $this->expiredGarbageCollection();
        }
        parent::afterFind();
    }

    public static function findByKey($key, $typeId, $allowExpired = false) {
        $query = $allowExpired === false
            ? 'user_key = :key AND type = :type AND expire > :now'
            : 'user_key = :key AND type = :type';
        $queryParams = [
            ':key' => $key,
            ':type' => $typeId
        ];
        if ($allowExpired === false) {
            $queryParams[':now'] = Yii::$app->formatter->asMysqlDatetime();
        }
        return static::find()->where($query, $queryParams)->one();
    }

    public static function findByUser($userId, $typeId, $allowExpired = false) {
        $query = $allowExpired === false
            ? 'user_id = :user_id AND type = :type AND expire > :now'
            : 'user_id = :user_id AND type = :type';
        $queryParams = [
            ':user_id' => $userId,
            ':type' => $typeId
        ];
        if ($allowExpired === false) {
            $queryParams[':now'] = Yii::$app->formatter->asMysqlDatetime();
        }
        return static::find()->where($query, $queryParams)->one();
    }

    public static function generateKey($userId, $typeId) {
        $key = static::findByUser($userId, $typeId);
        if (is_null($key)) {
            $key = new UserKey();
            $key->user_id = $userId;
            $key->type = $typeId;
            $key->save(false);
        }
        return $key;
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
    public function getUser()
    {
        return $this->hasOne(User::className(), ['person_id' => 'user_id']);
    }

    protected function expiredGarbageCollection() {
        $date = new \DateTime();
        $date->sub(new \DateInterval(static::getExpireInterval()));
        $garbageDate = Yii::$app->formatter->asMysqlDatetime($date);
        $expiredKeys = static::find()->where(
            'expire <= :expire',
            [
                ':expire' => $garbageDate
            ]
        )->all();

        $deleteIds = [];
        foreach ($expiredKeys as $expiredKey) {
            UserLog::add(UserLog::ACTION_USER_KEY, UserLog::RESULT_EXPIRED, $expiredKey->user_id, $expiredKey->expire);
            $deleteIds[] = $expiredKey->id;
        }
        static::deleteAll(['id' => $deleteIds]);
    }
}