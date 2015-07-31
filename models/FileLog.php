<?php

namespace wmc\models;

use Yii;
use wmc\behaviors\TimestampBehavior;
use wmc\helpers\IPHelper;

/**
 * This is the model class for table "{{%file_log}}".
 *
 * @property integer $id
 * @property integer $file_id
 * @property integer $result_type
 * @property integer $user_id
 * @property string $ip
 * @property string $created_at
 *
 * @property File $file
 */
class FileLog extends \wmc\db\ActiveRecord
{
    const RESULT_SUCCESS = 1;
    const RESULT_PERMISSIONS = -1;
    const RESULT_FILE_NOT_FOUND = -2;

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
        return '{{%file_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*
            [['file_id'], 'required'],
            [['file_id', 'result_type', 'user_id'], 'integer'],
            [['created_at'], 'safe'],
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
            'file_id' => 'File ID',
            'result_type' => 'Result Type',
            'user_id' => 'User ID',
            'ip' => 'Ip',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::className(), ['id' => 'file_id']);
    }

    public static function add($fileId, $result = self::RESULT_SUCCESS, $userId = null) {
        $log = new FileLog;
        $log->file_id = $fileId;
        $log->result_type = $result;
        $log->user_id = $userId;
        return $log->save(false);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert === true) {
                $this->ip = IPHelper::toBinaryIp(Yii::$app->request->getUserIP());
            }
            return true;
        } else {
            return false;
        }
    }
}