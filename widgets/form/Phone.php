<?php

namespace wmc\widgets\form;

use Yii;
use wmc\helpers\Html;

class Phone extends FormWidget
{

    public function init() {
        parent::init();

    }

    public function run() {
        return Html::tag('div', Html::tag('div',
                $this->form->field($this->model, 'full')->widget('yii\widgets\MaskedInput',
                    [
                        'options' => ['placeholder' => 'Your Phone (Optional)', 'class' => 'form-control'],
                        'mask' => "(999)999-9999"
                    ]
                )->label(false),
                ['class' => 'col-sm-6'])
            . Html::tag('div',
                $this->form->field($this->model, 'type_id')->dropDownList(\wmc\models\Phone::getTypeList([
                        \wmc\models\Phone::TYPE_MOBILE,
                        \wmc\models\Phone::TYPE_HOME,
                        \wmc\models\Phone::TYPE_OFFICE
                    ]
                ), ['prompt' => 'Phone Type...'])->label(false),
                ['class' => 'col-sm-6']),
            ['class' => 'row']);
    }

}