<?php

namespace wmc\widgets\bootstrap\input\file;

use yii\base\InvalidConfigException;
use yii\bootstrap\InputWidget;
use yii\helpers\ArrayHelper;
use wmc\models\FileTypeIcon;
use yii\bootstrap\Html;
use rmrevin\yii\fontawesome\FA;
use yii\helpers\VarDumper;

class FileInput extends InputWidget
{
    protected $_pluginOptions = [
        'showUpload' => false,
        'allowedPreviewTypes' => ['image']
    ];
    protected $_pluginEvents = [

    ];
    protected $_disabled = false;
    protected $_readonly = false;
    protected $_iconSet = 'glyphicon';

    public function setPluginOptions($options) {
        if (!empty($options) && is_array($options)) {
            $this->_pluginOptions = ArrayHelper::merge($this->_pluginOptions, $options);
        }
    }

    public function getPluginOptions() {
        return $this->_pluginOptions;
    }

    public function setPluginEvents($events) {
        if (!empty($events) && is_array($events)) {
            $this->_pluginEvents = ArrayHelper::merge($this->_pluginEvents, $events);
        }
    }

    public function getPluginEvents() {
        return $this->_pluginEvents;
    }

    public function setDisabled($disabled) {
        if (is_bool($disabled)) {
            $this->_disabled = $disabled;
        }
    }

    public function getDisabled() {
        return $this->_disabled;
    }

    public function setReadonly($readonly) {
        if (is_bool($readonly)) {
            $this->_readonly = $readonly;
        }
    }

    public function getReadonly() {
        return $this->_readonly;
    }

    public function setIconSet($iconSet) {
        if (!empty($iconSet) && is_string($iconSet)) {
            $this->_iconSet = strtolower($iconSet);
        }
    }

    public function getIconSet() {
        return $this->_iconSet;
    }

    public function init() {
        if (!$this->hasModel()) {
            throw new InvalidConfigException("FileInput widget only supports fields that are associated with a data model.");
        }
        // Auto Generate File Extension List if not already set
        if (!isset($this->_pluginOptions['allowedFileExtensions'])) {
            $this->_pluginOptions['allowedFileExtensions'] = $this->model->getAllowedFileExtensions($this->attribute);
        }
        // Icons
        $this->generatePreviewFileIcons();

        parent::init();
    }

    public function run() {
        return \kartik\file\FileInput::widget([
            'attribute' => $this->attribute,
            'model' => $this->model,
            'pluginOptions' => $this->_pluginOptions,
            'pluginEvents' => $this->_pluginEvents,
            'disabled' => $this->_disabled,
            'readonly' => $this->_readonly
        ]);
    }

    protected function getIconHtml($iconModel) {
        $name = !is_null($iconModel) ? $iconModel->name : 'file';
        $extraStyle = !is_null($iconModel) && !empty($iconModel->extra_style) ? $iconModel->extra_style : null;
        if ($this->getIconSet() == 'fa') {
            return (string) FA::icon($name, ['class' => $extraStyle]);
        } else {
            $options = [
                'tag' => 'i',
                'prefix' => $this->getIconSet() . ' ' . $this->getIconSet() . '-',
                'class' => $extraStyle
            ];
            return Html::icon($name, $options);
        }
    }

    protected function generatePreviewFileIcons() {
        $previewFileIconSettings = [];
        $icons = FileTypeIcon::find()
            ->alias('t')
            ->joinWith(['iconSet iconSet'])
            ->where(['iconSet.name' => $this->getIconSet()])
            ->andWhere(['in', 't.file_type_id', ArrayHelper::getColumn($this->model->getAllowedFileTypes($this->attribute), 'id')])
            ->all();
        foreach ($icons as $icon) {
            foreach ($icon->fileType->extensions as $extension) {
                $previewFileIconSettings[$extension->extension] = $this->getIconHtml($icon);
            }
        }
        $this->_pluginOptions['previewFileIconSettings'] = $previewFileIconSettings;
    }

    protected function getPreviewIcon() {
        $icon = FileTypeIcon::find()
            ->alias('t')
            ->joinWith('iconSet iconSet')
            ->where([
                    't.file_type_id' => $this->model->getUploadedFileModel($this->attribute)->file_type_id,
                    'iconSet.name' => $this->getIconSet()
                ]
            )
            ->one();
        //die(VarDumper::dumpAsString($this->getIconSet()));
        return Html::tag('span', $this->getIconHtml($icon), ['class' => 'file-icon-4x']);
    }
}