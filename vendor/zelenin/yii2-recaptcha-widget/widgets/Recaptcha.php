<?php

namespace Zelenin\yii\widgets\Recaptcha\widgets;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\InputWidget;

class Recaptcha extends InputWidget
{
    /** @var array */
    public $clientOptions = [];
    /** @var string */
    private $scriptUrl = '//www.google.com/recaptcha/api.js';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!isset($this->clientOptions['data-sitekey'])) {
            throw new InvalidConfigException('You should set data-sitekey');
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerAssets();

        echo $this->hasModel()
            ? Html::activeHiddenInput($this->model, $this->attribute)
            : Html::hiddenInput($this->name);

        $options = ['class' => 'g-recaptcha'];
        foreach (['data-sitekey', 'data-theme', 'data-type', 'data-callback'] as $dataAttribute) {
            if ($value = ArrayHelper::getValue($this->clientOptions, $dataAttribute)) {
                $options[$dataAttribute] = $value;
            }
        }
        echo Html::tag('div', '', $options);
    }

    private function registerAssets()
    {
        $view = $this->getView();

        $params = [];
        foreach (['onload', 'render', 'hl'] as $attribute) {
            if ($value = ArrayHelper::getValue($this->clientOptions, $attribute)) {
                $params[$attribute] = $value;
            }
        }
        $scriptUrl = $this->scriptUrl;
        if ($params) {
            $scriptUrl .= '?' . http_build_query($params);
        }
        $view->registerJsFile($scriptUrl);
    }
}
