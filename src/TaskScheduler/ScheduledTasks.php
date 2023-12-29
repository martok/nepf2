<?php
/**
 * Nepf2 Framework - Scheduled Tasks
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\TaskScheduler;

use Pop\Db\Adapter\Exception;

class ScheduledTasks extends \Pop\Db\Record
{
    public const tableName = 'nepf2_scheduled_tasks';

    public function __construct(?array $columns = null)
    {
        parent::__construct($columns, self::tableName);
    }

    public static function CreateTable(\Nepf2\Database\Database $db)
    {
        if (!$db->tableExists(self::tableName)) {
            $schema = $db->createSchema();
            $schema->create(self::tableName)
                ->int('id')->increment()
                ->bigInt('created')
                ->bigInt('deadline')
                ->int('interval')->nullable()
                ->varchar('class', 256)
                ->varchar('method', 256)->nullable()
                ->text('arguments')->defaultIs('')
                ->bigInt('started')->nullable()
                ->primary('id')
                ->index('deadline');
            $schema->execute();
        }

    }
}