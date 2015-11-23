<?php

namespace wmc\widgets\bootstrap;

use yii\base\Widget;
use wmc\widgets\bootstrap\ConfirmAsset;

class Confirm extends Widget
{
    public function init() {
        parent::init();
        ConfirmAsset::register($this->getView());
        $this->registerScript();
    }
    public function registerScript()
    {
        $this->getView()->registerJs("$('[data-toggle=\"confirmation\"]').confirmation();");
    }
}