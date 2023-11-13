<?php

namespace Nepf2;

interface IComponent
{
    public function __construct(Application $application);

    public function configure(array $config);
}