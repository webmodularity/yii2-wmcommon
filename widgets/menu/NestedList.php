<?php

namespace wmc\widgets\menu;

use yii\helpers\Html;
use wmc\models\Menu;

/**
 * Class ListMenu
 * @package wmc\widgets\menu
 * Supports OL and UL list types. Will nest list type to create list from nested-set data
 */

class NestedList extends NestedSetMenu
{
    public $currentId;
    public $listTag = 'ul';
    public $listOptions = [];
    public $listItemOptions = [];
    public $listNestedTag = 'ul';
    public $listNestedOptions = [];
    public $listItemNestedOptions = [];

    public function listItemCallable($item, $index, $nested) {
        $list = $item['list'];
        $item = $item['item'];
        $hasChildren = is_null($list) ? false : true;
        $listItemOptions = $nested === false ? $this->listItemOptions : $this->listItemNestedOptions;
        if ($this->currentId == $item->id) {
            Html::addCssClass($listItemOptions, "active");
        }
        return Html::tag('li', $this->buildItem($item, $index, $nested, $hasChildren) . $list, $listItemOptions);
    }

    public function run() {
        return $this->makeList($this->menuItems);
    }

    protected function makeList($items, $nested = false) {
        $listItems = [];
        foreach ($items as $item) {
            if (count($item['children']) > 0) {
                $listItems[] = [
                    'item' => $item,
                    'list' => $this->makeList($item['children'], true)
                ]
                ;
            } else {
                $listItems[] = [
                    'item' => $item,
                    'list' => null
                ];
            }
        }

        $listTag = $nested === false ? $this->listTag : $this->listNestedTag;
        $listOptions = $nested === false ? $this->listOptions : $this->listNestedOptions;
        $listOptions['item'] = function ($item, $index) use ($nested) {return $this->listItemCallable($item, $index, $nested);};
        return Html::$listTag($listItems, $listOptions);
    }

}