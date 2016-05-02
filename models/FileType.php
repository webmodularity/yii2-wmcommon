<?php

namespace wmc\models;

use Yii;
use yii\helpers\ArrayHelper;
use wmc\models\FileTypeQuery;
use wmc\models\FileTypeExtension;
use wmc\models\FileTypeMime;
use wmc\models\FileTypeIcon;

/**
 * This is the model class for table "common.file_type".
 *
 * @property integer $id
 * @property string $name
 * @property integer $allow_inline
 */
class FileType extends \wmc\db\ActiveRecord
{

    public static function find()
    {
        return new FileTypeQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'common.file_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['allow_inline'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'allow_inline' => 'Allow Inline',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(FileTypeCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMimeTypes()
    {
        return $this->hasMany(FileTypeMime::className(), ['file_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIcons()
    {
        return $this->hasMany(FileTypeIcon::className(), ['file_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExtensions()
    {
        return $this->hasMany(FileTypeExtension::className(), ['file_type_id' => 'id']);
    }

    public function getPrimaryExtension()
    {
        return $this->hasOne(FileTypeExtension::className(), ['file_type_id' => 'id'])->andOnCondition(['is_primary' => 1]);
    }

    public function getExtension() {
        return $this->primaryExtension->extension;
    }

    public static function getFileTypeList($fileTypeIds = []) {
        $query = FileType::find()->orderBy(['name' => SORT_ASC]);
        if (!empty($fileTypeIds) && is_array($fileTypeIds)) {
            $query->andWhere(['in', 'id', $fileTypeIds]);
        }

        return ArrayHelper::map($query->all(), 'id', 'name');
    }
}