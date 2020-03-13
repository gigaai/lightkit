<?php

if ( ! function_exists('array_only')) {
    /**
     * Retrieve only some fields in array
     */
    function array_only(array $array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}