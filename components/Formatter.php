<?php

namespace wmc\components;

use Yii;
use DateTime;
use DateTimeZone;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

class Formatter extends \yii\i18n\Formatter
{
    public $dateFormat = 'php:m/d/Y';
    public $datetimeFormat = 'php:m/d/Y h:i:s A';
    public $timeFormat = 'php:h:i:s A';

    /**
     * Takes either a DateTime object, timestamp integer, or null (default) = time()
     * Used for inserting Datetime strings into MySQL, assumes web server and MySQL server are in same timezone.
     * @param $date DateTime|integer|null Datetime or timestamp to format
     * @return string Datetime string suitable for insert into MySQL DATETIME or TIMESTAMP column
     */

    public function asMysqlDatetime($date = null) {
        $date = empty($date) ? time() : $date;
        return $date instanceof DateTime ? $date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $date);
    }

    /**
     * Takes either a DateTime object, timestamp integer, or null (default) = time()
     * Used for inserting Date strings into MySQL, assumes web server and MySQL server are in same timezone.
     * @param $date DateTime|integer|null Datetime or timestamp to format
     * @return string Date string suitable for insert into MySQL DATE column
     */

    public static function asMysqlDate($date = null) {
        $date = empty($date) ? time() : $date;
        return $date instanceof DateTime ? $date->format('Y-m-d') : date('Y-m-d', $date);
    }

    /**
     * This function converts a human readable IPv4 or IPv6 address into an address family appropriate
     * 32bit or 128bit binary structure. Suitable for MySQL VARBINARY(16) columns.
     * @param $ip A human readable IPv4 or IPv6 address.
     * @return null|string Returns the in_addr representation of the given ip or NULL if ip is invalid
     */

    public static function asBinaryIp($ip) {
        $inetPton = inet_pton($ip);
        return $inetPton === false ? null : $inetPton;
    }

    /**
     * This function converts a 32bit IPv4, or 128bit IPv6 address (if PHP was built with IPv6 support enabled) into
     * an address family appropriate string representation.  Suitable for MySQL VARBINARY(16) columns.
     * @param $ip A 32bit IPv4, or 128bit IPv6 address
     * @return null|string Returns a string representation of the address or NULL on failure
     */

    public static function asIp($ip) {
        $inetNtop = inet_ntop($ip);
        return $inetNtop === false ? null : $inetNtop;
    }

    /**
     * Formats the value as a localized datetime.
     * @param integer|string|DateTime $value the value to be formatted. The following
     * types of value are supported:
     *
     * - an integer representing a UNIX timestamp
     * - a string that can be [parsed to create a DateTime object]
     * - a PHP [DateTime](http://php.net/manual/en/class.datetime.php) object
     *
     * @param string $format the format used to convert the value into a date string.
     * If null, [[dateFormat]] will be used.
     *
     * This can be "short", "medium", "long", or "full", which represents a preset format of different lengths.
     * It can also be a custom format as specified in the [ICU manual](http://userguide.icu-project.org/formatparse/datetime).
     *
     * Alternatively this can be a string prefixed with `php:` representing a format that can be recognized by the
     * PHP [date()](http://php.net/manual/de/function.date.php)-function.
     *
     * @return string the formatted result.
     * @throws InvalidParamException if the input value can not be evaluated as a date value.
     * @throws InvalidConfigException if the date format is invalid.
     * @see datetimeFormat
     */

    public function asLocalDateTime($value = null, $format = null, $timeZone = null) {
        if (empty($value)) {
            $value = "now";
        }

        // Convert format
        if (empty($format)) {
            $format = 'm/d/Y h:i:s A T';
        }

        if (empty($timeZone)) {
            $timeZone = !empty(Yii::$app->params['defaultTimeZone'])
                ? Yii::$app->params['defaultTimeZone']
                : Yii::$app->timeZone;
        }
        try {
            $localTimeZone = new DateTimeZone($timeZone);
        } catch (Exception $e) {
            throw new InvalidConfigException("Failed to set timeZone! Error: ".$e->getMessage()."");
        }

        $serverDate = $this->normalizeDatetimeValue($value);

        if ($serverDate === null) {
            return $this->nullDisplay;
        }

        // Localize Date
        $localDate = $serverDate->setTimezone($localTimeZone);

        return $localDate->format($format);
    }

}