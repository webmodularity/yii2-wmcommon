<?php

namespace wmc\widgets\form;

use Yii;
use wmc\helpers\Html;
use yii\base\InvalidConfigException;

class Phone extends FormWidget
{

    protected $_typeValues = [
        \wmc\models\Phone::TYPE_MOBILE => 'Mobile',
        \wmc\models\Phone::TYPE_HOME => 'Home',
        \wmc\models\Phone::TYPE_OFFICE => 'Office'
    ];

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
                $this->form->field($this->model, 'type_id')->dropDownList($this->_typeValues, ['prompt' => 'Phone Type...'])->label(false),
                ['class' => 'col-sm-6']),
            ['class' => 'row']);
    }

}