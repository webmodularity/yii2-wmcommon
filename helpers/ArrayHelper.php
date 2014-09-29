<?php

namespace wmc\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{

    /**
     * Behaves much like ArrayHelper::merge() but merges class rather than replacing $b['class'] with $a['class].
     * If $a['class'] = 'class1 class2' and $b['class'] = 'class3' this function will return $c['class'] = 'class1 class2 class3'.
     * $recurse param takes an array in case the 'class' key is more than 1 layer deep.
     * For example, if $a['options']['class'] set $recurse to ['options'] or
     * ['options']['deep'] in case of $a['options']['deep']['class']
     * @param $a array first array
     * @param $b array second array
     * @param array $recurse array containing keys corresponding to depth of class index
     * @return array The merged array
     */

    public static function mergeClass($a, $b, $recurse = [])
    {
        $classA = &$a;
        $classB = &$b;
        if (is_array($recurse) && count($recurse) > 0) {
            foreach ($recurse as $r) {
                $classA = &$classA[$r];
                $classB = &$classB[$r];
            }
        }
        if (!isset($classA['class']) || !isset($classB['class'])) {
            // No class meging needed
            return parent::merge($a, $b);
        } else {
            $classAList = explode(' ', parent::remove($classA, 'class'));
            $classBList = explode(' ', parent::remove($classB, 'class'));
            $classA['class'] = implode(' ', parent::merge($classAList, $classBList));
            //die(var_dump($a));
            return parent::merge($a, $b);
        }
    }

}