<?php

namespace wmc\widgets\bootstrap;

use yii\base\Widget;
use wmc\widgets\bootstrap\ConfirmationAsset;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

class DeleteConfirm extends Widget
{
    public function init() {
        parent::init();
        ConfirmationAsset::register($this->getView());
        $this->registerScript();
    }
    public function registerScript()
    {
        $this->getView()->registerJs("$('[data-toggle=\"delete-confirm\"]').confirmation(".Json::encode([
                'btnOkClass' => 'btn btn-sm btn-success',
                'btnOkLabel' => 'Yes',
                'btnCancelClass' => 'btn btn-sm btn-default',
                'btnCancelLabel' => 'No',
                'onConfirm' => new JsExpression("function(event, element){
                    var deleteUrl = element.data('href');
                    var csrfParam = yii.getCsrfParam();
                    var obj = {};
                    obj[csrfParam] = yii.getCsrfToken();
                    $.redirect(deleteUrl, obj, 'POST');
                    event.preventDefault();
                }")
            ]).");", View::POS_READY, 'delete-confirm');
    }
}