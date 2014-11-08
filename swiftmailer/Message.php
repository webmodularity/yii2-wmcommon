<?php

namespace wmc\swiftmailer;

class Message extends \yii\swiftmailer\Message
{
    private $_title = 'Untitled';

    public function setTitle($title) {
        if (is_string($title)) {
            $this->_title = $title;
        }
    }

    public function getTitle() {
        return $this->_title;
    }
}