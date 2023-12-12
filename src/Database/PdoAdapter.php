<?php
/**
 * Nepf2 Framework - Database
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Database;

// This class must be named something containing 'PDO' for the query builders to work
class PdoAdapter extends \Pop\Db\Adapter\Pdo
{
    public function dbFileExists(): true
    {
        // always assume the file exists. if not, it will be created by sqlite
        return true;
    }

}