<?php

namespace wmc\helpers;

use yii\helpers\Inflector;

class ConstantHelper
{

    /**
     * TODO: Write better docs
     * @param string $prefix
     * @param array $excludeValues
     * @return array|null|string
     */

    public static function humanizedList($className, $prefix, $excludeValues = []) {
        $reflection = new \ReflectionClass($className);
        $constants = $reflection->getConstants();
        $constantList = [];
        foreach ($constants as $cName => $cVal) {
            if (!empty($prefix)) {
                if (substr($cName, 0, strlen($prefix)) != $prefix || in_array($cVal, $excludeValues)) {
                    continue;
                }
                $constantList[$cVal] = Inflector::humanize(strtolower(substr($cName, (strlen($prefix) - 1))), true);
            } else {
                $constantList[$cVal] = Inflector::humanize(strtolower($cName), true);
            }
        }
        return $constantList;
    }

    public static function humanizedFromValue($className, $prefix, $value) {
        $list = self::humanizedList($className, $prefix);
        return isset($list[$value]) ? $list[$value] : 'Undefined';
    }

}