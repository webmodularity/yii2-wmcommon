<?php

namespace wmc\widgets\menu;

use Yii;
use wmc\models\Menu;
use yii\base\InvalidConfigException;
use wmc\helpers\NestedSetHelper;
use yii\helpers\Html;

class NestedSetMenu extends \yii\base\Widget
{
    protected $_rootNode = null;
    protected $_menu = null;
    protected $_menuItems = [];
    protected $_userPermissionFilter = true;

    public $menuName = "Main";
    public $menuId = null;
    public $childDepth = 3;

    /**
     * @param $userPermissionFilter bool Only return results viewable by currently logged in user
     */

    public function setUserPermissionFilter($userPermissionFilter) {
        if (is_bool($userPermissionFilter)) {
            $this->_userPermissionFilter = $userPermissionFilter;
        }
    }

    public function init() {
        // Find Root Node
        if (!empty($this->menuId) && is_int($this->menuId)) {
            $this->_rootNode = Menu::find()->where(['id' => $this->menuId])->roots()->one();
        } else {
            $this->_rootNode = Menu::find()->where(['name' => $this->menuName])->roots()->one();
        }

        if (is_null($this->_rootNode)) {
            //die(var_dump($this));
            throw new InvalidConfigException("Unable to locate root node of menu!");
        }

        if ($this->_userPermissionFilter === false) {
            $menuItems = $this->_rootNode
                ->children($this->childDepth)
                ->all();
        } else {
            $userGroupId = Yii::$app->user->isGuest ? 0 : Yii::$app->user->identity->group_id;
            $menuItems = $this->_rootNode
                ->children($this->childDepth)
                ->andWhere(['user_group_id' => $userGroupId])
                ->joinWith('userGroups')
                ->all();
        }
        $this->_menuItems = NestedSetHelper::toHierarchy($menuItems);
    }

    public function getMenuItems() {
        return $this->_menuItems;
    }

    public function getMenu() {
        return $this->_rootNode;
    }

    public static function iconTag($icon, $iconSet, $fixedWidth = false, $iconSize = null) {
        if (empty($icon)) {
            return '';
        }

        if (!empty($iconSet)) {
            $iconClass = "".$iconSet." ".$iconSet."-".$icon."";
            if ($fixedWidth === true) {
                $iconClass .= " ".$iconSet."-fw";
            }
            if (!empty($iconSize)) {
                $iconClass .= ' ' . $iconSet.'-'.$iconSize;
            }
            return Html::tag('i', '', ['class' => $iconClass]) . "&nbsp;";
        }
    }

    protected function buildItem($item, $index, $nested = false, $hasChildren = false) {
        if ($item->type == Menu::TYPE_DIVIDER) {
            return "[DIVIDER]";
        } else if ($item->type == Menu::TYPE_HEADER) {
            return Html::tag('span', static::iconTag($item->icon, $this->menu->icon) . $item->name, ['class' => 'label label-default']);
        } else {
            return Html::a(static::iconTag($item->icon, $this->menu->icon) . $item->name, $item->link);
        }
    }

}