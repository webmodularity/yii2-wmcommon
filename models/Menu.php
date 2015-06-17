<?php

namespace wmc\models;

use Yii;
use wmu\models\UserGroup;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * This is the model class for table "{{%menu}}".
 *
 * @property integer $id
 * @property integer $tree_id
 * @property integer $type
 * @property integer $lft
 * @property integer $rgt
 * @property integer $depth
 * @property string $name
 * @property string $link
 * @property string $icon
 *
 * @property UserGroup[] $userGroups
 */
class Menu extends \wmc\db\ActiveRecord
{
    public $children = [];

    const TYPE_ROOT = 0;
    const TYPE_LINK = 1;
    const TYPE_HEADER = 10;
    const TYPE_DIVIDER = 20;

    public function behaviors() {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                'treeAttribute' => 'tree_id'
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
        return '{{%menu}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
           // [['tree_id'], 'required'],
            [['tree_id', 'type', 'lft', 'rgt', 'depth'], 'integer'],
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
            'tree_id' => 'Tree',
            'type' => 'Type',
            'lft' => 'Lft',
            'rgt' => 'Rgt',
            'depth' => 'Depth',
            'name' => 'Name',
            'link' => 'Link',
            'icon' => 'Icon',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserGroups()
    {
        return $this->hasMany(UserGroup::className(), ['id' => 'user_group_id'])->viaTable('{{%menu_access}}', ['menu_id' => 'id']);
    }

    public function getMenuItemList() {
        // Make sure this menu is a root node
        $menuId = $this->id;
        $root = static::find()->where(['id' => $menuId])->orderBy(['id' => SORT_ASC])->roots()->one();
        if (is_null($root)) {
            return [];
        }

        $menuItems = [$menuId => 'None (Append or Prepend Only)'];
        $nodes = $root->children()->all();
            foreach ($nodes as $node) {
                if ($node->type === self::TYPE_HEADER) {
                    $displayName = "[HEADER] " . $node->name;
                } else if ($node->type === self::TYPE_DIVIDER) {
                    $displayName = "[DIVIDER]";
                } else {
                    $displayName = !empty($node->icon) ? "".$node->name." [".$node->icon."]" : $node->name;
                }
                $menuItems[$node->id] = str_repeat('>', $node->depth) . ' ' . $displayName;
            }
        return $menuItems;
    }
}