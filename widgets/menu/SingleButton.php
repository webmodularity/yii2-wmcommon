<?php

namespace wmc\widgets\menu;

use wmc\models\MenuItem;
use Yii;
use wmc\helpers\Html;

class SingleButton extends NestedSetMenu
{
    protected $_listItems = [];

    public $buttonLabel = "Menu";
    public $buttonOptions = [];
    public $divOptions = [];
    public $listOptions = [];

    public function init() {
        // Call before to set $_menuItems
        parent::init();
        foreach ($this->menuItems as $item) {
            $this->setNextListItem($item);
            if (count($item->children) > 0) {
                // Only go 1 level deep
                foreach ($item->children as $child) {
                    $this->setNextListItem($child);
                }
            }
        }
    }

    public function run() {
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
        return Html::tag(
            'div',
            Html::button($this->buttonLabel . '&nbsp;' . Html::tag('i', '', ['class' => 'caret']), $this->buttonOptions)
            . Html::tag('ul', implode("\n", $this->_listItems), $this->listOptions)
            , $this->divOptions);
    }

    protected function setNextListItem($item) {
        if ($item->type == MenuItem::TYPE_HEADER) {
            // Header
            $this->_listItems[] = Html::tag('li', $item->name, ['class' => 'dropdown-header']);
        } else if ($item->type == MenuItem::TYPE_DIVIDER) {
            $this->_listItems[] = Html::tag('li', '', ['class' => 'divider']);
        } else {
            // Link
            if (!empty($item->icon)) {
                if (!empty($this->menu) && !empty($this->menu->icon_set)) {
                    $icon_set = $this->menu->icon_set;
                } else {
                    $icon_set = null;
                }
                $iconClass = empty($icon_set) ? $item->icon : $icon_set . ' ' . $item->icon;
                $icon = Html::tag('i', '', ['class' => $iconClass]) . "&nbsp;";
            } else {
                $icon = null;
            }

            $this->_listItems[] = Html::tag('li', Html::a($icon . $item->name, $item->link));
        }
    }
}