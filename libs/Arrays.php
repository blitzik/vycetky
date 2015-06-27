<?php

namespace blitzik\Arrays;

class Arrays
{
    public static function count_recursive($array, $depth) {
        $count = 0;
        foreach ($array as $id => $_array) {
            if (is_array ($_array) && $depth > 0) {
                $count += self::count_recursive ($_array, $depth - 1);
            } else {
                $count += 1;
            }
        }
        return $count;
    }
}