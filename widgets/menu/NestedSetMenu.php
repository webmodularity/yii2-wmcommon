<?php

namespace wmc\widgets\menu;

use Yii;
use wmc\models\MenuItem;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use wmc\helpers\NestedSetHelper;
use yii\helpers\VarDumper;

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

        $this->_menu = $this->_rootNode->menu;
        $this->mapMenuItems();

        die(var_dump($this->_menuItems));
    }

    protected function mapMenuItems() {
        $menuItems = $this->_rootNode->children($this->childDepth)->all();
        NestedSetHelper::toMenuArray($menuItems);
        $groupId = Yii::$app->user->isGuest() ? null : Yii::$app->user->identity->group_id;
        $menuRootDepth = 1;
        $menuItemKey = 0;
        foreach ($menuItems as $key => $item) {
            if ($item->depth == $menuRootDepth) {
                $menuItemKey = $menuItemKey == 0 ? 0 : $menuItemKey + 1;
                $this->menuItems[$menuItemKey] = $item;
            } else {
                #$this->addMenuItemChildren();
            }
        }
    }

    protected function addMenuItemChildren($menuItem, $menuRootDepth = 1) {

    }
}