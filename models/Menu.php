<?php

namespace wmc\models;

use Yii;
use wmc\models\user\UserGroup;
use wmc\behaviors\NestedSetsBehavior;
use wmc\behaviors\UserGroupAccessBehavior;

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
            [
                'class' => UserGroupAccessBehavior::className(),
                'viaTableName' => '{{%menu_access}}',
                'itemIdField' => 'menu_id'
            ],
            'tree' => [
                'class' => NestedSetsBehavior::className(),
                'treeAttribute' => 'tree_id'
            ]
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
            [['tree_id', 'type', 'lft', 'rgt', 'depth'], 'integer'],
            [['type'], 'in', 'range' => [static::TYPE_ROOT, static::TYPE_LINK, static::TYPE_DIVIDER, static::TYPE_HEADER]],
            [['name', 'link', 'icon'], 'string', 'max' => 255],
            [['name', 'link', 'icon'], 'trim'],
            [['name', 'link', 'icon'], 'default', 'value' => null],
            [['type'], 'required'],
            [['name', 'link'], 'required', 'when' => function($model) {
                return $model->type == static::TYPE_LINK;
            }, 'whenClient' => "function (attribute, value) {
                    return $('input[name=\"Menu[type]\"]:checked').val() == '".static::TYPE_LINK."';
                    }"
            ],
            [['name'], 'required', 'when' => function($model) {
                return in_array($model->type, [static::TYPE_ROOT, static::TYPE_HEADER]);
            }, 'whenClient' => "function (attribute, value) {
                    var typeVal = $('#menu-type').attr('type') == 'hidden' ? $('#menu-type').val() : $('input[name=\"Menu[type]\"]:checked').val();
                    var validTypes = ['".static::TYPE_ROOT."', '".static::TYPE_HEADER."'];
                    return $.inArray(typeVal, validTypes) == -1 ? false : true;
                    }"
            ],
            [['name'], 'unique',
                'targetClass' => '\wmc\models\Menu',
                'targetAttribute' => 'name',
                'filter' => ['type' => static::TYPE_ROOT],
                'when' => function($model) {
                    return $model->type == static::TYPE_ROOT;
                }
            ]
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
            'lft' => 'Left',
            'rgt' => 'Right',
            'depth' => 'Depth',
            'name' => 'Name',
            'link' => 'Link',
            'icon' => 'Icon Name',
        ];
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