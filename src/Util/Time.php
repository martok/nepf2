<?php
/**
 * Nepf2 Framework - Util
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Util;

class Time
{
    public static function Millis(): int
    {
        return (int)(microtime(true) * 1000);
    }
}