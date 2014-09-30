<?php

namespace wmc\components;

class Formatter extends \yii\i18n\Formatter
{
    public function asMysqlDatetime($datetime = 'now') {
        return date("Y-m-d H:i:s", strtotime($datetime));
    }

    public function asMysqlDate($date = 'now') {
        return date("Y-m-d", strtotime($date));
    }

}