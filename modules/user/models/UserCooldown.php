<?php

namespace wmu\models;

use Yii;

/**
 * This is the model class for table "user_cooldown".
 *
 * @property string $ip
 * @property string $expire
 */
class UserCooldown extends \wmc\db\ActiveRecord
{
    public static $duration = "PT1H";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_cooldown';
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
            if ($insert === true) {
                $cooldownTime = new \DateTime();
                $cooldownTime->add(new \DateInterval(static::$duration));
                $this->expire = Yii::$app->formatter->asMysqlDatetime($cooldownTime);
            }
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
                'ip = :ip AND expire > :expire',
                [
                    ':ip' => Yii::$app->formatter->asBinaryIP($ip),
                    ':expire' => Yii::$app->formatter->asMysqlDatetime()
                ]
            )->exists();
        }
    }

}