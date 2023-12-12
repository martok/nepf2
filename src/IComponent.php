<?php
/**
 * Nepf2 Framework
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2;

interface IComponent
{
    public function __construct(Application $application);

    public function configure(array $config);
}