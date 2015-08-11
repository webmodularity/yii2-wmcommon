<?php

namespace wmc\widgets\form;

use Yii;
use yii\helpers\Html;

class Phone extends FormWidget
{
    public $numberLabel = false;
    public $numberPlaceholder = 'Your Phone (Optional)';
    public $typeLabel = false;

    public function init() {
        parent::init();
    }

    public function run() {
        return Html::tag('div', Html::tag('div',
                $this->form->field($this->model, 'full')->widget('yii\widgets\MaskedInput',
                    [
                        'options' => ['placeholder' => $this->numberPlaceholder, 'class' => 'form-control'],
                        'mask' => "(999)999-9999"
                    ]
                )->label($this->numberLabel),
                ['class' => 'col col-xs-7 col-sm-6'])
            . Html::tag('div',
                $this->form->field($this->model, 'type_id')->dropDownList(\wmc\models\Phone::getTypeList([
                        \wmc\models\Phone::TYPE_MOBILE,
                        \wmc\models\Phone::TYPE_HOME,
                        \wmc\models\Phone::TYPE_OFFICE
                    ]
                ))->label($this->typeLabel),
                ['class' => 'col col-xs-5 col-sm-6']),
            ['class' => 'row']);
    }

}