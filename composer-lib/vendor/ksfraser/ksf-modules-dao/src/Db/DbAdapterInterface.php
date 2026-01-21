<?php

namespace Ksfraser\ModulesDAO\Db;

interface DbAdapterInterface
{
    /**
     * Dialect identifier for schema generation.
     * Typical values: mysql, sqlite.
     */
    public function getDialect(): string;

    public function getTablePrefix(): string;

    /**
     * Execute a query and return all results as an array of associative arrays
     *
     * @param string $sql The SQL query with optional named parameters (e.g., :param)
     * @param array $params Associative array of parameter values
     * @return array<int, array<string, mixed>> Array of result rows
     */
    public function query(string $sql, array $params = []): array;

    /**
     * Execute a statement (DDL/DML).
     */
    public function execute(string $sql, array $params = []): void;

    /**
     * @return int|null
     */
    public function lastInsertId(): ?int;
}