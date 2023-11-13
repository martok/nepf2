<?php

namespace Nepf2\Router;

use Nepf2\Application;
use Nepf2\Request;
use Nepf2\Response;
use ReflectionMethod;

class RouteMatcher
{
    private array $method = [];
    public string $expression = '';
    public bool $slash;
    public int $fixedLength = 0;
    public int $priority = 0;
    public ReflectionMethod $boundMethod;
    public array $nameIndexMap;
    public array $nameTypeMap;
    public array $defaultValues;
    public ?array $boundValues = null;

    public function setMethod(array $method): void
    {
        $this->method = array_map('strtoupper', $method);
    }

    private function decodePathVariable(string $name, string $value): mixed
    {
        return match ($this->nameTypeMap[$name]->getName()) {
            'string' => urldecode($value),
            'array' => array_map('urldecode', explode('/', $value)),
            default => $value,
        };
    }

    public function matchMethod(string $method): bool
    {
        $method = strtoupper($method);
        return !count($this->method) ||
            in_array($method, $this->method);
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

    /**
     * Create the Controller and call the bound method of a matched Route
     *
     * @param Application $app
     * @param Request $request
     * @param Response $response
     * @return bool Returns false to indicate that the response should not be sent.
     */
    public function executeRoute(Application $app, Request $request, Response $response): bool
    {
        $controllerClass = $this->boundMethod->getDeclaringClass();
        $controller = $controllerClass->newInstance($app);
        return false !== $this->boundMethod->invoke($controller, $response, $request, ...$this->boundValues);
    }

}