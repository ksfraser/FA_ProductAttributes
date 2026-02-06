<?php

namespace Ksfraser\FA_ProductAttributes\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Fixed FrontAccounting database adapter that properly implements DbAdapterInterface
 * This is a wrapper around the vendor version that fixes the missing lastInsertId method
 */
class FrontAccountingDbAdapter implements DbAdapterInterface
{
    private $tablePrefix;

    public function __construct(string $tablePrefix = '0_')
    {
        $this->tablePrefix = $tablePrefix;
    }

    public function getDialect(): string
    {
        return 'mysql';
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function query(string $sql, array $params = []): array
    {
        // Use FA's db_query function
        $result = db_query($sql, 'could not execute query');

        $rows = [];
        while ($row = db_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function execute(string $sql, array $params = []): void
    {
        // Use FA's db_query function
        db_query($sql, 'could not execute query');
    }

    public function lastInsertId(): ?int
    {
        // Use FA's db_insert_id function
        return db_insert_id();
    }
}