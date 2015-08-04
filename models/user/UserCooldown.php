<?php

namespace wmc\models\user;

use Yii;
use wmc\helpers\IPHelper;

/**
 * This is the model class for table "user_cooldown".
 *
 * @property string $ip
 * @property string $expire
 */
class UserCooldown extends \wmc\db\ActiveRecord
{
    public static $duration = "PT15M";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_cooldown}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['ip'], 'required'],
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
            'ip' => 'Ip',
            'expire' => 'Expire',
        ];
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $cooldownTime = new \DateTime();
            $cooldownTime->add(new \DateInterval(static::$duration));
            $this->expire = static::getMysqlDatetime($cooldownTime);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks the user_cooldown table to see if this IP is currently on cooldown
     * @param $ip IP address to check, most likely derived from $_SERVER['REMOTE_ADDR']
     * @return bool true if IP found in user_cooldown table, a null IP returns false
     */

    public static function IPOnCooldown($ip) {
        if (is_null($ip)) {
            return false;
        } else {
            return UserCooldown::find()->where(
                'ip = :ip AND expire > NOW()',
                [
                    ':ip' => IPHelper::toBinaryIp($ip)
                ]
            )->exists();
        }
    }

}