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

#[Attribute(Attribute::TARGET_CLASS)]
class RoutePrefix
{
    public string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

}