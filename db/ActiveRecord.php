<?php

namespace wmc\db;

use yii\helpers\Inflector;

class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * Takes either a DateTime object, timestamp integer, or null (default) = time()
     * Used for inserting Datetime strings into MySQL, assumes web server and MySQL server are in same timezone.
     * @param $date \DateTime|integer|null Datetime or timestamp to format
     * @return string Datetime string suitable for insert into MySQL DATETIME or TIMESTAMP column
     */

    public static function getMysqlDatetime($date = null) {
        $date = empty($date) ? time() : $date;
        return $date instanceof \DateTime ? $date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $date);
    }

    /**
     * Takes either a DateTime object, timestamp integer, or null (default) = time()
     * Used for inserting Date strings into MySQL, assumes web server and MySQL server are in same timezone.
     * @param $date \DateTime|integer|null Datetime or timestamp to format
     * @return string Date string suitable for insert into MySQL DATE column
     */

    public static function getMysqlDate($date = null) {
        $date = empty($date) ? time() : $date;
        return $date instanceof \DateTime ? $date->format('Y-m-d') : date('Y-m-d', $date);
    }

    public static function getReadableConstantList($prefix = '', $key = null) {
        $reflection = new \ReflectionClass(static::className());
        $constants = $reflection->getConstants();
        $constantList = [];
        foreach ($constants as $cName => $cVal) {
            if (!empty($prefix)) {
                if (substr($cName, 0, strlen($prefix)) != $prefix) {
                    continue;
                }
                $humanized = Inflector::humanize(substr($cName, (strlen($prefix) - 1)));
                if (!empty($key)) {
                    if ($key == $cVal) {
                        return $humanized;
                    }
                } else {
                    $constantList[$cVal] = $humanized;
                }
            }
        }
        return !empty($key) ? null : $constantList;
    }

    /**
     * Takes a full AR model with an unset PK and returns AR result if record is found
     * @return ActiveRecord|null null if no result found
     */

    public function findOneFromAttributes() {
        return $this->findOne($this->getAttributes(null, $this->primaryKey()));
    }

}