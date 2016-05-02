<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "common.file_type_icon".
 *
 * @property integer $file_type_id
 * @property integer $icon_set_id
 * @property string $name
 * @property string $extra_style
 */
class FileTypeIcon extends \wmc\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.file_type_icon';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_type_id', 'icon_set_id', 'name'], 'required'],
            [['extra_style'], 'default', 'value' => null],
            [['file_type_id', 'icon_set_id'], 'integer'],
            [['name', 'extra_style'], 'string', 'max' => 50],
            [['file_type_id', 'icon_set_id', 'name'], 'unique', 'targetAttribute' => ['file_type_id', 'icon_set_id', 'name'], 'message' => 'The combination of File Type ID, Icon Set ID and Name has already been taken.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'file_type_id' => 'File Type ID',
            'icon_set_id' => 'Icon Set ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFileType()
    {
        return $this->hasOne(FileType::className(), ['id' => 'file_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIconSet()
    {
        return $this->hasOne(IconSet::className(), ['id' => 'icon_set_id']);
    }
}