<?php

namespace wmc\helpers;

class NestedSetHelper
{

    public static function toHierarchy($collection) {
        $trees = $stack = [];

        foreach ($collection as $item) {
            $stackCount = count($stack);

            // Check if we're dealing with different levels
            while($stackCount > 0 && $stack[$stackCount - 1]->depth >= $item->depth) {
                array_pop($stack);
                $stackCount--;
            }

            // Stack is empty (we are inspecting the root)
            if ($stackCount == 0) {
                // Assigning the root node
                $i = count($trees);
                $trees[$i] = $item;
                $stack[] = & $trees[$i];
                } else {
                // Add node to parent
                $i = count($stack[$stackCount - 1]->children);
                $stack[$stackCount - 1]->children[$i] = $item;
                $stack[] = & $stack[$stackCount - 1]->children[$i];
                }
            }
        return $trees;
    }

}