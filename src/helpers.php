<?php

if (!function_exists('env')) {
    function env(string $varname, $default = null)
    {
        return $_ENV[$varname] ?? $default;
    }
}

if (!function_exists('makeCollection')) {
    function makeCollection(array $array)
    {
        return new \Tightenco\Collect\Support\Collection($array);
    }
}
