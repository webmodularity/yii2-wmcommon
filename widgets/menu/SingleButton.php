<?php

namespace wmc\widgets\menu;

use wmc\models\Menu;
use Yii;
use wmc\helpers\Html;

class SingleButton extends NestedSetMenu
{
    protected $_listItems = [];

    public $buttonLabel = "Menu";
    public $buttonOptions = [];
    public $divOptions = [];
    public $listOptions = [];

    public function buildItem($item, $index, $nested = false, $hasChildren = false) {
        if ($item->type == Menu::TYPE_HEADER) {
            return Html::tag('li', static::iconTag($item->icon, $this->menu->icon) . $item->name, ['class' => 'dropdown-header']);
        } else if ($item->type == Menu::TYPE_DIVIDER) {
            return Html::tag('li', '', ['class' => 'divider']);
        } else {
            return Html::tag('li', Html::a(static::iconTag($item->icon, $this->menu->icon) . $item->name, $item->link), []);
        }
    }

    public function init() {
        // Call before to set $_menuItems
        parent::init();
        $this->divOptions['class'] = !empty($this->divOptions['class'])
            ? $this->divOptions['class'] . ' dropdown'
            : 'dropdown';
        $this->buttonOptions['class'] = !empty($this->buttonOptions['class'])
            ? $this->buttonOptions['class'] . ' btn dropdown-toggle'
            : 'btn dropdown-toggle';
        $this->buttonOptions['data-toggle'] = 'dropdown';
        $this->listOptions['class'] = !empty($this->listOptions['class'])
            ? 'dropdown-menu ' . $this->listOptions['class']
            : 'dropdown-menu';
        $this->listOptions['role'] = 'menu';
        $this->listOptions['item'] = function($item, $index) {
            return $this->buildItem($item, $index, false, false);
        };
        foreach ($this->menuItems as $item) {
            $this->_listItems[] = $item;
            if (count($item->children) > 0) {
                // Only go 1 level deep
                foreach ($item->children as $child) {
                    $this->_listItems[] = $child;
                }
            }
        }
    }

    public function run() {
        return Html::beginTag('div', $this->divOptions)
            . Html::button($this->buttonLabel . '&nbsp;' . Html::tag('i', '', ['class' => 'caret']), $this->buttonOptions)
            . Html::ul($this->_listItems, $this->listOptions)
            . Html::endTag('div');
    }
}