<?php

namespace wmc\helpers;

class NestedSetHelper
{
    /**
     * Takes a one dimensional nested set result and returns an array with children property set.
     * @param array $menuTree a one dimensional array of node objects with a depth property
     * @param integer $rootMenuDepth depth of menu root
     */

    public static function toMenuArray(array $menuTree, $rootMenuDepth = 1) {
        $menuRoots = [];
        $rootKey = 0;
        while (count($menuTree) > 0) {
            $menuRoots[$rootKey] = array_shift($menuTree);
            $menuChildren = [];
            if (count($menuTree) > 0) {
                foreach ($menuTree as $key => $child) {
                    if ($child->depth > $rootMenuDepth) {
                        $menuChildren[] = $child;
                        unset($menuTree[$key]);
                    } else {
                        break;
                    }
                }
            }
            die(var_dump($menuChildren));
        }
    }

    public static function mapMenuTree(array $menuTree) {
        if (count($menuTree) > 1) {
            $menuItems = [];
            $root = array_shift($menuTree);
            $rootDepth = $root->depth;
            foreach ($menuTree as $nextMenu) {
                
            }
        }
        return $menuTree;
    }

    public static function isLeaf($menuTree, $key) {
        if (empty($menuTree) || (count($menuTree) - 1) == $key) {
            return true;
        }
        $nextDepth = $menuTree[$key+1]->depth;
        if ($menuTree[$key]->depth > $nextDepth) {
            return true;
        } else {
            return false;
        }
    }

    public static function appendMenuChild($menuTree, $object) {
        $child = array_shift($objects);
        if ($child->depth > $object->depth) {
            return static::appendChild($objects, $child);
        } else {
            #return $object->children
        }
    }

    public static function findMenuTreeLength(array $nestedObjects, $startingKey) {
        if (is_array($nestedObjects) && count($nestedObjects) > 0 && is_int($startingKey)) {
            $slicedObjects = array_slice($nestedObjects, $startingKey);
            $count = $rootDepth = 0;
            foreach ($slicedObjects as $object) {
                if ($count == 0) {
                    $rootDepth = $object->depth;
                    $count++;
                } else if ($object->depth == $rootDepth) {
                    return $count;
                } else {
                    $count++;
                }
            }
            return $count;
        }
        return 1;
    }
}