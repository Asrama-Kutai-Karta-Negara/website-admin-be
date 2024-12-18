<?php

use Illuminate\Support\Str;

if (!function_exists('convertToSnakeCase')) {
    /**
     * Convert camelCase keys to snake_case.
     *
     * @param array $data
     * @return array
     */
    function convertToSnakeCase(array $data)
    {
        $converted = [];
        foreach ($data as $key => $value) {
            $converted[Str::snake($key)] = $value;
        }
        return $converted;
    }
}

if (!function_exists('convertToCamelCase')) {
    /**
     * Convert camelCase keys to snake_case.
     *
     * @param array $data
     * @return array
     */
    function convertToCamelCase(array $data)
    {
        $converted = [];
        foreach ($data as $key => $value) {
            $converted[Str::camel($key)] = $value;
        }
        return $converted;
    }
}
