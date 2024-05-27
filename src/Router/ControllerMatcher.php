<?php
/**
 * Nepf2 Framework - Router
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Router;

use Nepf2\Application;
use Nepf2\Request;
use Nepf2\Response;
use ReflectionMethod;

class ControllerMatcher extends RouteMatcher
{
    public array $nameIndexMap;
    public array $nameTypeMap;
    public ?array $boundValues = null;
    public ReflectionMethod $boundMethod;
    public array $defaultValues;

    private function decodePathVariable(string $name, string $value): string|array
    {
        return match ($this->nameTypeMap[$name]->getName()) {
            'string' => urldecode($value),
            'array' => array_map('urldecode', explode('/', $value)),
            default => $value,
        };
    }

    public function matchPath(string $path): bool
    {
        if (1 === preg_match($this->expression, $path, $vars)) {
            $this->boundValues = $this->defaultValues;
            foreach ($this->nameIndexMap as $name => $index) {
                $this->boundValues[$index] = $this->decodePathVariable($name, $vars[$name]);
            }
            return true;
        }
        return false;
    }

    public function executeRoute(Application $app, Request $request, Response $response): bool
    {
        $controllerClass = $this->boundMethod->getDeclaringClass();
        $controller = $controllerClass->newInstance($app);
        return false !== $this->boundMethod->invoke($controller, $response, $request, ...$this->boundValues);
    }
}