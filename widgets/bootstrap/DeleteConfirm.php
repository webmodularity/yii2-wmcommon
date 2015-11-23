<?php

namespace wmc\widgets\bootstrap;

use yii\base\Widget;
use wmc\widgets\bootstrap\ConfirmAsset;
use yii\helpers\Json;

class DeleteConfirm extends Widget
{
    public function init() {
        parent::init();
        ConfirmAsset::register($this->getView());
        $this->registerScript();
    }
    public function registerScript()
    {
        $this->getView()->registerJs("$('[data-toggle=\"confirmation\"]').confirmation(".Json::encode([
                'btnOkClass' => 'btn btn-sm btn-success',
                'btnOkLabel' => 'Yes',
                'btnCancelClass' => 'btn btn-sm btn-danger',
                'btnCancelLabel' => 'No',
                'popout' => false
            ]).");");
    }
}