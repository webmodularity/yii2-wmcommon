<?php

namespace wmc\models;

use Yii;
use wmu\models\UserGroup;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * This is the model class for table "{{%menu_item}}".
 *
 * @property integer $id
 * @property integer $menu_id
 * @property string $name
 * @property integer $type
 * @property integer $lft
 * @property integer $rgt
 * @property integer $depth
 * @property string $link
 * @property string $icon
 *
 * @property Menu $menu
 * @property UserGroup[] $userGroups
 */
class MenuItem extends \wmc\db\ActiveRecord
{
    public function behaviors() {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                    'treeAttribute' => 'menu_id'
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public static function find()
    {
        return new MenuQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%menu_item}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['menu_id', 'name'], 'required'],
            [['menu_id', 'type', 'lft', 'rgt', 'depth'], 'integer'],
            [['name', 'link', 'icon'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'menu_id' => 'Menu ID',
            'name' => 'Name',
            'type' => 'Type',
            'lft' => 'Lft',
            'rgt' => 'Rgt',
            'depth' => 'Depth',
            'link' => 'Link',
            'icon' => 'Icon',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenu()
    {
        return $this->hasOne(Menu::className(), ['id' => 'menu_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserGroups()
    {
        return $this->hasMany(UserGroup::className(), ['id' => 'user_group_id'])->viaTable('{{%menu_item_access}}', ['menu_item_id' => 'id']);
    }
}