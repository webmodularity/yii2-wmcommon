<?php

namespace wmc\behaviors;

use wmc\models\Menu;
use wmc\db\ActiveRecord;
use Closure;

class MenuSyncBehavior extends \yii\base\Behavior
{
    public $menuTitleAttribute = 'menu_title';

    public $menuName = 'Main';
    public $menuCategoryName;
    public $userGroups = [];

    protected $_link;
    protected $_rootNode = false;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete'
        ];
    }

    public function setLink($link) {
        $this->_link = $link;
    }

    public function getLink() {
        if ($this->_link instanceof Closure || is_array($this->_link) && is_callable($this->_link)) {
            return call_user_func($this->_link, $this->owner);
        } else {
            return $this->_link;
        }
    }

    public function getRootNode() {
        if ($this->_rootNode === false) {
            $rootNode = Menu::find()->roots()->andWhere(['name' => $this->menuName])->limit(1)->one();

            if (!empty($this->menuCategoryName)) {
                $this->_rootNode = $rootNode->children(1)->andWhere(['name' => $this->menuCategoryName])->limit(1)->one();
            } else {
                $this->_rootNode = $rootNode;
            }
        }

        return $this->_rootNode;
    }

    public function afterUpdate($event) {
        if (in_array($this->menuTitleAttribute, array_keys($event->changedAttributes))) {
            if (is_null($this->menuModel)) {
                // Add menu item
                $this->addMenuItem();
            } else {
                if ($this->isValueEmpty) {
                    // Delete menu item
                    $this->deleteMenuItem($this->menuModel);
                } else {
                    // Update name attribute of menu item
                    $this->updateMenuItem($this->menuModel);
                }
            }
        }
    }

    public function afterInsert($event) {
        // Add menu item
        $this->addMenuItem();
    }

    public function afterDelete($event) {
        // Delete menu item
        $this->deleteMenuItem($this->menuModel);
    }

    protected function getMenuModel() {
        return $this->rootNode->children(1)->andWhere(['link' => $this->link])->limit(1)->one();
    }

    protected function getTitleValue() {
        return $this->owner->{$this->menuTitleAttribute};
    }

    protected function getIsValueEmpty() {
        return empty($this->titleValue) ? true : false;
    }

    protected function addMenuItem() {
        // Add menu row
        $menuItem = new Menu(
            [
                'name' => $this->owner->{$this->menuTitleAttribute},
                'link' => $this->link,
                'type' => Menu::TYPE_LINK,
                'userGroupIds' => $this->userGroups
            ]
        );
        return $menuItem->appendTo($this->rootNode);
    }

    protected function deleteMenuItem($menuModel) {
        // Delete menu item
        return $menuModel->delete();
    }

    protected function updateMenuItem($menuModel) {
        // Update menu.name
        $menuModel->name = $this->titleValue;
        return $menuModel->save(true, ['name']);
    }

}