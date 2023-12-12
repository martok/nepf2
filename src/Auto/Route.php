<?php
/**
 * Nepf2 Framework - Automation
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Auto;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public const ALL_METHODS = ['GET', 'POST', 'PUT'];

    public string $fragment;
    public int $priority;
    public array $method;
    public bool $slash;

    public function __construct(string $fragment, int $priority = 0, string|array $method = self::ALL_METHODS,
                                bool $slash = false)
    {
        $this->fragment = $fragment;
        $this->priority = $priority;
        if (!is_array($method))
            $method = [$method];
        $this->method = $method;
        $this->slash = $slash;
    }
}