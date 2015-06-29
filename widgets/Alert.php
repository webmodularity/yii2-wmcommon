<?php

namespace wmc\widgets;

use Yii;
use wmc\helpers\Html;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use rmrevin\yii\fontawesome\FA;

/*
 * Creates bootstrap style Alert. Be sure to include bootstrap css and js files as this widget doesn't load assets.
 * Bootstrap JS needed for close functionality. FontAwesome needed if icon specified.
 */

class Alert extends Widget
{
    public $encode = true;

    public $flashId = 'alert';

    public $message = null;
    public $heading = null;
    public $style = 'warning';
    public $close = true;
    public $icon = null;

    private $_validStyles = ['warning', 'success', 'info', 'danger'];
    private $_isBlank = false;

    public function init() {
        if (is_null($this->message) && is_null($this->heading)) {
            // Try and populate fields from Yii::$app->session->getFlash($this->flashId)
            $flash = Yii::$app->session->getFlash($this->flashId);
            if (!is_null($flash)) {
                $this->heading = isset($flash['heading']) && !empty($flash['heading'])
                    ? $flash['heading'] : $this->heading;
                $this->message = isset($flash['message']) && !empty($flash['message'])
                    ? $flash['message'] : $this->message;
                $this->style = isset($flash['style']) && in_array($flash['style'], $this->_validStyles)
                    ? $flash['style'] : $this->style;
                $this->icon = isset($flash['icon']) ? $flash['icon'] : $this->icon;
            } else {
                $this->_isBlank = true;
            }
        }

        if ($this->_isBlank === false) {
            // Check configs
            if (!is_bool($this->close) || !in_array($this->style, $this->_validStyles)
                || (is_null($this->message) && is_null($this->heading))
            ) {
                throw new InvalidConfigException("Invalid config settings passed to Alert Widget!");
            }
        }

        // Normalize encode
         if (!is_bool($this->encode)) {
             $this->encode = true;
         }
    }

    public function getContainerOptions() {
        return [
            'class' => 'alert alert-' . $this->style,
            'role' => 'alert'
        ];
    }

    public function getMessageHtml() {
        return $this->encode === true
            ? Html::encode($this->message)
            : $this->message;
    }

    public function getHeadingHtml() {
        return Html::tag('strong', $this->heading) . '&nbsp;';
    }

    public function getCloseHtml() {
        return $this->close === true ? Html::button('×', ['class' => 'close', 'data-dismiss' => 'alert']) : '';
    }

    public function getIconHtml() {
        if ($this->icon === false || empty($this->icon)) {
            return '';
        } else {
            return FA::icon(Html::encode($this->icon))->fixedWidth() . '&nbsp;';
        }
    }

    public function getIsBlank() {
        return $this->_isBlank;
    }

    public function run() {
        if ($this->_isBlank === true) {
            return '';
        } else {
            return Html::tag(
                'div',
                $this->getCloseHtml() . $this->getIconHtml() . $this->getHeadingHtml() . $this->getMessageHtml(),
                $this->getContainerOptions()
            );
        }
    }
}