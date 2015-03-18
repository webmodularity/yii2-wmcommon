<?php

namespace wmc\widgets\menu;

use Yii;
use wmc\models\MenuItem;
use yii\base\InvalidConfigException;
use wmc\helpers\NestedSetHelper;

class NestedSetMenu extends \yii\base\Widget
{
    protected $_rootNode = null;
    protected $_menu = null;
    protected $_menuItems = [];

    public $menuName = "Main";
    public $menuId = null;
    public $childDepth = 3;

    public function init() {
        // Find Root Node
        if (!empty($this->menuId) && is_int($this->menuId)) {
            $this->_rootNode = MenuItem::find()->where(['menu_id' => $this->menuId])->roots()->one();
        } else {
            $this->_rootNode = MenuItem::find()->where(['name' => $this->menuName])->roots()->one();
        }

        if (is_null($this->_rootNode)) {
            throw new InvalidConfigException("Unable to locate root node of menu!");
        }

        $userGroupId = Yii::$app->user->isGuest ? 0 : Yii::$app->user->identity->group_id;
        $this->_menu = $this->_rootNode->menu;
        $menuItems = $this->_rootNode
            ->children($this->childDepth)
            ->andWhere(['user_group_id' => $userGroupId])
            ->joinWith('userGroups')
            ->all();
        $this->_menuItems = NestedSetHelper::toHierarchy($menuItems);
    }

    public function getMenuItems() {
        return $this->_menuItems;
    }

    public function getMenu() {
        return $this->_menu;
    }
}