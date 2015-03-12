<?php

namespace wmc\widgets\menu;

use Yii;
use wmc\helpers\Html;
use wmc\models\Menu;

class SingleButton extends NestedSetMenu
{
    public function init() {
        // Call before to set $_rootNode
        parent::init();
        $this->_menuItems[] = Html::a(Html::tag('i', '', ['class' => "glyphicon glyphicon-home"]) . "&nbsp;Home", ['/']);

        if (!Yii::$app->user->isGuest) {
            $this->_menuItems[] = Html::a(Html::tag('i', '', ['class' => "glyphicon glyphicon-trash"])
                . "&nbsp;Spring Cleanup - 2015", ['/page/spring-cleanup']);
            $this->_menuItems[] = Html::a(Html::tag('i', '', ['class' => "glyphicon glyphicon-thumbs-up"])
                . "&nbsp;Architectural Control Committee", ['/page/architectural-control-committee']);
            $this->_menuItems[] = Html::a(Html::tag('i', '', ['class' => "glyphicon glyphicon-tree-deciduous"])
                . "&nbsp;Weed Control", ['/file/weed-control.pdf']);
            $this->_menuItems[] = Html::a(Html::tag('i', '', ['class' => "glyphicon glyphicon-paperclip"])
                . "&nbsp;Download CC&R's", ['/file/ccrs.pdf']);
        }

        $this->_menuItems[] = Html::a(Html::tag('i', '', ['class' => "glyphicon glyphicon-envelope"]) . "&nbsp;Contact Us", ['/contact']);
        parent::init();
    }

    public function run() {
        $buttonClass = $this->white === true ? "btn dropdown-toggle outline-white" : "btn dropdown-toggle";
        return Html::tag('div',
            Html::button('Site Menu&nbsp;' . Html::tag('i', '', ['class' => 'caret']),
                ['class' => $buttonClass, 'data-toggle' => "dropdown", 'aria-expanded' => "false"])
            . Html::ul($this->_menuItems, ['class' => "dropdown-menu pull-right", 'role' => "menu", 'encode' => false])
            , ['class' => "btn-group pull-right"]);
    }
}