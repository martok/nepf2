<?php
/**
 * Nepf2 Framework - Database
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Database;

use Nepf2\Application;
use Nepf2\IComponent;
use Nepf2\Util\Arr;
use Pop\Db;
use Pop\Db\Adapter\AdapterInterface;

class Database implements IComponent
{
    public const ComponentName = "db";
    private Application $app;
    private Db\Adapter\AbstractAdapter $db;
    private string $migrationsPath;
    private string $migrationStateFile;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    public function configure(array $config)
    {
        $config = Arr::ExtendConfig([
            'type' => '',
            'host' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'options' => '',
            'migrations_path' => 'app/database',
            'migrations_state' => 'data/db_migration.current'
        ], $config);
        if ('sqlite' === $config['type']) {
            $config['database'] = $this->app->expandPath($config['database']);
        }
        $db = new PdoAdapter($config);
        Db\Record::setDb($db);
        $this->db = $db;
        $this->migrationsPath = $this->app->expandPath($config['migrations_path']);
        $this->migrationStateFile = $this->app->expandPath($config['migrations_state']);
    }

    public function query(string $sql): AdapterInterface
    {
        return $this->db->query($sql);
    }

    public function prepare(string $sql)
    {
        $this->db->prepare($sql);
    }

    public function bindParams(array $params): AdapterInterface
    {
        return $this->db->bindParams($params);
    }

    public function execute(): AdapterInterface
    {
        return $this->db->execute();
    }

    public function fetch(): array
    {
        return $this->db->fetch();
    }

    public function fetchAll(): array
    {
        return $this->db->fetchAll();
    }

    public function getNumberOfRows(): int
    {
        return $this->db->getNumberOfRows();
    }

    public function getLastId(): int
    {
        return $this->db->getLastId();
    }

    public function getTables(): array
    {
        return $this->db->getTables();
    }

    public function beginTransaction(): AdapterInterface
    {
        return $this->db->beginTransaction();
    }

    public function commit(): AdapterInterface
    {
        return $this->db->commit();
    }

    public function rollback(): AdapterInterface
    {
        return $this->db->rollback();
    }

    public function createSql(): Db\Sql
    {
        return $this->db->createSql();
    }

    public function createSchema(): Db\Sql\Schema
    {
        return $this->db->createSchema();
    }

    public function migrator(): Migrator
    {
        $mig = new Migrator($this->db, $this->migrationsPath);
        $mig->setCurrentFile($this->migrationStateFile);
        return $mig;
    }

    public function tableExists(string $table): bool
    {
        $q = $this->db->createSql();
        $q->select(['dummy' => 'COUNT(1)'])->from($table)->limit(1);
        try {
            $this->db->query($q);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }
}