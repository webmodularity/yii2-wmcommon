<?php

namespace wmc\models\user;

use Yii;
use wmc\behaviors\FindOrInsertBehavior;

/**
 * This is the model class for table "user_log_path".
 *
 * @property integer $id
 * @property string $path
 *
 * @property UserCooldownLog[] $userCooldownLogs
 * @property UserLog[] $userLogs
 */
class UserLogPath extends \wmc\db\ActiveRecord
{
    public function behaviors() {
        return [
            'findOrInsert' =>
                [
                    'class' => FindOrInsertBehavior::className()
                ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_log_path}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['path'], '\wmc\validators\TruncateValidator', 'length' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Path',
        ];
    }

}