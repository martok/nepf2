<?php
/**
 * Nepf2 Framework - Util
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Util;

class Arr
{
    /**
     * Produce the union set of one or more arrays.
     *
     * @param array ...$arrays
     * @return array
     */
    public static function Union(... $arrays): array
    {
        $set = [];
        foreach ($arrays as $array) {
            foreach ($array as $item) {
                $set[$item] = true;
            }
        }
        return array_keys($set);
    }

    /**
     * Recursively merge arrays similar to array_merge_recursive($defaults, $values), but
     * by default keeping only keys that exists in $defaults, dropping the rest.
     *
     * @param array $defaults
     * @param array $values
     * @param bool $onlyExisting
     * @return array
     */
    public static function ExtendConfig(array $defaults, array $values, bool $onlyExisting=true): array
    {
        $merged = [];
        $mergeKeys = array_keys($defaults);
        if (!$onlyExisting)
            $mergeKeys = self::Union($mergeKeys, array_keys($values));
        foreach ($mergeKeys as $key) {
            if (isset($values[$key])) {
                if (is_array($defaults[$key]))
                    $merged[$key] = self::ExtendConfig($defaults[$key], $values[$key]);
                else {
                    $merged[$key] = $values[$key];
                }
            } else {
                $merged[$key] = $defaults[$key];
            }
        }
        return $merged;
    }

    /**
     * Access a value in nested arrays using a.b.c notation. Non-existing arrays are created.
     * The callback $action is called on the final array with the key name as argument.
     *
     * @param array &$array array reference
     * @param string $slug
     * @param callable|null $action fn(&$array, $key) => $result
     * @return mixed the value returned by $action, or the final value or null if no callback given
     */
    public static function DottedAccess(array &$array, string $slug, ?callable $action = null): mixed
    {
        // any key before the last addresses an array, the last may
        // address a value (or something non-existing)
        $keys = explode('.', $slug);
        $trailKey = array_pop($keys);
        foreach ($keys as $key) {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        if (is_null($action)) {
            return $array[$trailKey] ?? null;
        }
        return $action($array, $trailKey);
    }
}