<?php

namespace Nepf2\Util;

class Path
{
    public static function Normalize(string $path): string
    {
        if (DIRECTORY_SEPARATOR !== '/')
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        return preg_replace('#/{2,}#', '/', $path);
    }

    public static function Canonicalize(string $path): string
    {
        // Removing .. and .
        $pathParts = explode('/', self::Normalize($path));
        $newPathParts = [];
        foreach ($pathParts as $pathPart) {
            switch ($pathPart) {
                case '':
                case '.':
                    break;
                case '..':
                    if (!count($newPathParts))
                        throw new \InvalidArgumentException('Path attempts to access outside root.');
                    array_pop($newPathParts);
                    break;
                default:
                    $newPathParts[] = $pathPart;
                    break;
            }
        }

        return implode('/', $newPathParts);
    }

    public static function IsAbsolute(string $path): bool
    {
        return strspn($path, '/\\', 0, 1)
            || (\strlen($path) > 3 && ctype_alpha($path[0])
                && ':' === $path[1]
                && strspn($path, '/\\', 2, 1)
            )
            || null !== parse_url($path, \PHP_URL_SCHEME);
    }

    public static function IncludeTrailingSlash(string $path): string
    {
        if (str_ends_with($path, '/'))
            return $path;
        return $path . '/';
    }

    public static function ExpandRelative(string $base, string $path): string
    {
        $path = self::Normalize($path);
        if (self::IsAbsolute($path))
            return $path;
        return self::Canonicalize($base . \DIRECTORY_SEPARATOR . $path);
    }

    public static function Join(string ...$paths): string
    {
        $result = implode('/', $paths);
        return self::Normalize($result);
    }

    public static function Pop(string $path, bool $trailing=true): array
    {
        if (!$trailing)
            $path = rtrim($trailing, '/');
        $pos = strrpos($path, '/');
        if ($pos !== false) {
            return [
                substr($path, 0, $pos),
                substr($path, $pos+1)
            ];
        }
        return ['', $path];
    }

}