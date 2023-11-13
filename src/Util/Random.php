<?php

namespace Nepf2\Util;

class Random
{
    const BASE36 = 'abcdefghijklmnopqrstuvwxyz0123456789';

    public static function TokenStr(int $len = 6): string
    {
        return implode('', array_map(fn ($v) => self::BASE36[ord($v) % 36],
                                     str_split(random_bytes($len))));
    }

}