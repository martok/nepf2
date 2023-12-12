<?php
/**
 * Nepf2 Framework - Router
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Router;

use ReflectionMethod;
use ReflectionNamedType;

class RouteExpression
{
    static array $defaultDeclParameters;
    public array $urlParamNames = [];
    private array $urlSegments = [];
    private bool $slash = false;

    public static function Split(string $fragment): array
    {
        return array_filter(explode('/', $fragment), 'strlen');
    }

    public static function TypeToRegex(ReflectionNamedType $type): ?string
    {
        switch ($type->getName()) {
            case 'int':
                return '\d+';
            case 'array':
            case 'string':
                return '[^/]+';
        }
        return null;
    }

    public function __construct(string $path, bool $slash)
    {
        self::__initStatic();
        $parts = self::Split($path);
        $slugs = [];
        foreach ($parts as $part) {
            if (preg_match('#^<(\S+?)(?::(.*))?(\*)?>$#', $part, $m)) {
                $n = $m[1];
                $regex = isset($m[2]) && strlen($m[2]) ? $m[2] : null;
                $ispath = isset($m[3]) && strlen($m[3]);
                $slugs[] = [$n, $regex, $ispath];
                $this->urlParamNames[] = $n;
            } else {
                $slugs[] = $part;
            }
        }
        $this->urlSegments = $slugs;
        $this->slash = $slash;
    }

    protected static function __initStatic() {
        if (!isset(self::$defaultDeclParameters)) {
            $fun = fn(string $str) => false;
            $type = (new \ReflectionParameter($fun, 0))->getType();
            self::$defaultDeclParameters = [
                'decltype' => $type,
                'bindtype' => self::TypeToRegex($type)
            ];
        }
    }

    public function bind(ReflectionMethod $handlerMethod, array $declParameters): RouteMatcher
    {
        $pvalues = [];
        $pmap = [];
        $ptypes = [];
        foreach ($declParameters as $name => $data) {
            if (in_array($name, $this->urlParamNames, true)) {
                // parameter is in url, save the location for later
                $pmap[$name] = count($pvalues);
                $pvalues[] = null;
            } else {
                // parameter is not in url, always call with default
                $pvalues[] = $data['default'];
            }
        }
        $fixed = 0;
        $expr = '';
        foreach ($this->urlSegments as $segment) {
            $expr .= '/';
            if (is_string($segment)) {
                // fixed string
                $expr .= preg_quote($segment, '%');
            } else {
                if (0 === $fixed) {
                    $fixed = strlen($expr);
                }
                [$name, $regex, $ispath] = $segment;
                $data = $declParameters[$name] ?? self::$defaultDeclParameters;
                $ptypes[$name] = $data['decltype'];
                if (is_null($regex)) {
                    $regex = $data['bindtype'];
                }
                if ($ispath) {
                    // path segments consist of their type delimited with /, or nothing
                    $regex = '(?:' . $regex . '(?:/' . $regex . ')*)?';
                }
                $expr .= '(?P<' . $name . '>' . $regex . ')';
            }
        }
        if ('' === $expr) {
            $expr = '/';
        }
        if (0 === $fixed) {
            $fixed = strlen($expr);
        }
        if ($this->slash)
            $expr .= '/?';
        $matcher = new RouteMatcher();
        $matcher->expression = '%^' . $expr . '$%';
        $matcher->fixedLength = $fixed;
        $matcher->boundMethod = $handlerMethod;
        $matcher->nameIndexMap = $pmap;
        $matcher->nameTypeMap = $ptypes;
        $matcher->defaultValues = $pvalues;
        return $matcher;
    }

}