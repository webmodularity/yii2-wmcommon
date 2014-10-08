<?php

namespace wma\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_key_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $key_type
 * @property integer $action_type
 * @property string $created_at
 * @property string $ip
 *
 * @property User $user
 */
class UserKeyLog extends \wmc\models\ActiveRecord
{
    const ADD = 1;
    const USED = 2;
    const EXPIRED = 3;

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
        return 'user_key_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['user_id', 'key_type', 'log_type', 'log_time', 'ip'], 'required'],
            [['user_id', 'key_type', 'log_type'], 'integer'],
            [['log_time'], 'safe'],
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
            'user_id' => 'User ID',
            'key_type' => 'Key Type',
            'action_type' => 'Action Type',
            'created_at' => 'Create Time',
            'ip' => 'IP Address',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['person_id' => 'user_id']);
    }
}
