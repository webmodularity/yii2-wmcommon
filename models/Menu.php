<?php

namespace wmc\models;

use Yii;

/**
 * This is the model class for table "{{%menu}}".
 *
 * @property integer $id
 * @property string $icon_set
 *
 * @property MenuItem[] $menuItems
 */
class Menu extends \wmc\db\ActiveRecord
{
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
            [['icon_set'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'icon_set' => 'Icon Set',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenuItems()
    {
        return $this->hasMany(MenuItem::className(), ['menu_id' => 'id']);
    }

    public static function getMenuList() {
        $menus = static::find()->all();
        $list = [];
        foreach ($menus as $menu) {
            $iconSet = empty($menu->icon_set) ? '' : ' [' . $menu->icon_set . ']';
            $list[$menu->id] = $menu->id . $iconSet;
        }
        return $list;
    }
}