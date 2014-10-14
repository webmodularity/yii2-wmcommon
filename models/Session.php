<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "session".
 *
 * @property string $id
 * @property string $expire
 * @property string $data
 *
 * @property UserLog[] $userLogs
 */
class Session extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'session';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        /*
        return [
            [['id'], 'required'],
            [['id'], 'string', 'max' => 64],
            [['expire', 'data'], 'string', 'max' => 45]
        ];
        */
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'expire' => 'Expire',
            'data' => 'Data',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserLogs()
    {
        return $this->hasMany(UserLog::className(), ['session_id' => 'id']);
    }
}