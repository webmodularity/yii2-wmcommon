<?php

namespace wmu\models;

use Yii;

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
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_log_path';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['path'], 'truncate', 'length' => 255],
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserCooldownLogs()
    {
        return $this->hasMany(UserCooldownLog::className(), ['path_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserLogs()
    {
        return $this->hasMany(UserLog::className(), ['path_id' => 'id']);
    }

    /**
     * Returns the path ID
     * @param bool|string $path The path string, if false path will be derived from app->request->pathInfo
     * @return int Returns ID of path record or ID of '' path or 1 if cannot be found
     */

    public static function generateId($path = false)
    {
        if ($path === false) {
            $path = Yii::$app->request->pathInfo;
        }
        $pathModel = static::findOneOrInsert(['path' => $path]);
        if (is_null($pathModel)) {
            $pathModel = static::findOneOrInsert(['path' => '']);
            if (is_null($pathModel)) {
                return 1;
            }
        }
        return $pathModel->id;
    }

}