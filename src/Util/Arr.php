<?php

namespace Nepf2\Util;

class Arr
{
    /**
     * Recursively merge arrays similar to array_merge_recursive($defaults, $values), but
     * keeping only keys that exists in $defaults, dropping the rest.
     *
     * @param array $defaults
     * @param array $values
     * @return array
     */
    public static function ExtendConfig(array $defaults, array $values): array
    {
        $merged = [];
        foreach (array_keys($defaults) as $key) {
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