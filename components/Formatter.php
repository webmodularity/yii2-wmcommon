<?php

namespace wmc\components;

class Formatter extends \yii\i18n\Formatter
{
    public function asMysqlDatetime($datetime = 'now') {
        return self::asDatetime($datetime, "php:Y-m-d H:i:s");
    }

    public function asMysqlDate($date = 'now') {
        return self::asDate($date, "php:Y-m-d");
    }

    public function asBinaryIp($ip) {
        $inetPton = inet_pton($ip);
        return $inetPton === false ? null : $inetPton;
    }

}