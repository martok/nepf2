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
use Nepf2\Auto;
use Nepf2\IComponent;
use Nepf2\Request;
use Nepf2\Response;
use Nepf2\Util\ClassUtil;
use Nepf2\Util\Path;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use TypeError;

class Router implements IComponent
{
    public const ComponentName = "router";
    private Application $app;
    private array $routeMatchers = [];

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    public function configure(array $config)
    {
    }

    public function addControllers(array $classes, string $prefix = "")
    {
        foreach ($classes as $className) {
            $reflectionClass = new ReflectionClass($className);
            // Process Controller-wide prefix
            $classPrefix = Path::Join($prefix, $this->getRoutingPrefix($reflectionClass));
            // Process Route definitions
            foreach ($this->getRoutingMethods($reflectionClass) as [$reflectionMethod, $routeAttribs]) {
                $errPrefix = "AutoRoute on {$reflectionClass->getName()}::{$reflectionMethod->getName()}";
                // check the formal parameters
                $params = $this->checkFormalParameters($reflectionMethod->getParameters(), $errPrefix);
                // collect other parameter info we may need
                $declParameters = $this->getDeclParameters($params, $errPrefix);
                $requiredParams = array_keys(array_filter($declParameters, function ($p) {
                    return $p['required'];
                }));
                // we now know where everything goes, parse what we need for each possible route
                foreach ($routeAttribs as $routeAttrib) {
                    /* @var $route Auto\Route */
                    $route = $routeAttrib->newInstance();
                    // parse the route expression
                    $expr = new RouteExpression(Path::Join($classPrefix, $route->fragment), $route->slash);
                    if (count(array_diff($requiredParams, $expr->urlParamNames))) {
                        throw new TypeError("$errPrefix: Route on {$route->fragment} does not bind all parameters");
                    }
                    // compile to a matcher and resulting method call
                    $matcher = $expr->bind($reflectionMethod, $declParameters);
                    $matcher->priority = $route->priority;
                    $matcher->setMethod($route->method);
                    $this->routeMatchers[] = $matcher;
                }
            }
        }
        $this->sortMatchers();
    }

    protected static function getRoutingPrefix(ReflectionClass $reflectionClass): string
    {
        if ($classAttribs = $reflectionClass->getAttributes(Auto\RoutePrefix::class, \ReflectionAttribute::IS_INSTANCEOF)) {
            $inst = $classAttribs[0]->newInstance();
            return $inst->prefix;
        }
        return '';
    }

    protected static function getRoutingMethods(ReflectionClass $reflectionClass): array
    {
        $result = [];
        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (count($routeAttribs = $reflectionMethod->getAttributes(Auto\Route::class, ReflectionAttribute::IS_INSTANCEOF)))
                $result[] = [$reflectionMethod, $routeAttribs];
        }
        return $result;
    }

    private function sortMatchers(): void
    {
        usort($this->routeMatchers, function ($a, $b) {
            // highest priority first
            if ($a->priority < $b->priority)
                return 1;
            elseif ($a->priority > $b->priority)
                return -1;
            // longest fixed part first
            if ($a->fixedLength < $b->fixedLength)
                return 1;
            elseif ($a->fixedLength > $b->fixedLength)
                return -1;
            // then shortest total expression
            elseif (strlen($a->expression) < strlen($b->expression))
                return -1;
            elseif (strlen($a->expression) > strlen($b->expression))
                return 1;
            return 0;
        });
    }

    public function match(string $method, string $path): ?RouteMatcher
    {
        foreach ($this->routeMatchers as $matcher) {
            if ($matcher->matchMethod($method) &&
                $matcher->matchPath($path)) {
                return $matcher;
            }
        }
        return null;
    }

    /**
     * @param ReflectionParameter[] $params
     * @param string $errPrefix
     * @return array[]
     */
    private function getDeclParameters(array $params, string $errPrefix): array
    {
        $declParameters = [];
        foreach ($params as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType ||
                null === ($regex = RouteExpression::TypeToRegex($type))) {
                throw new TypeError("$errPrefix: Bad type for {$param->getName()}");
            }
            $declParameters[$param->getName()] = [
                'param' => $param,
                'required' => !$param->isOptional(),
                'decltype' => $type,
                'bindtype' => $regex,
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }
        return $declParameters;
    }

    /**
     * @param ReflectionParameter[] $params
     * @param string $errPrefix
     * @return ReflectionParameter[]
     */
    private function checkFormalParameters(array $params, string $errPrefix): array
    {
        if (!($p = array_shift($params)) ||
            !ClassUtil::ParamIsClass($p, Response::class)) {
            throw new TypeError("$errPrefix: Missing Response parameter");
        }
        if (($p = array_shift($params)) &&
            !ClassUtil::ParamIsClass($p, Request::class)) {
            throw new TypeError("$errPrefix: Bad Request parameter");
        }
        return $params;
    }

}