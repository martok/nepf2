<?php

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