<?php

namespace Nepf2\Util;

class Time
{
    public static function Millis(): int
    {
        return (int)(microtime(true) * 1000);
    }
}