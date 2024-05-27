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

abstract class RouteMatcher
{
    public int $priority = 0;
    private array $method = [];
    public string $expression = '';
    public int $fixedLength = 0;

    public function setMethod(array $method): void
    {
        $this->method = array_map('strtoupper', $method);
    }

    public function matchMethod(string $method): bool
    {
        $method = strtoupper($method);
        return !count($this->method) ||
            in_array($method, $this->method);
    }

    abstract public function matchPath(string $path): bool;

    /**
     * Create the Controller and call the bound method of a matched Route
     *
     * @param Application $app
     * @param Request $request
     * @param Response $response
     * @return bool Returns false to indicate that the response should not be sent.
     */
    abstract public function executeRoute(Application $app, Request $request, Response $response): bool;
}