<?php

namespace Ksfraser\FA_ProductAttributes\Db;

interface DbAdapterInterface
{
    /**
     * Dialect identifier for schema generation.
     * Typical values: mysql, sqlite.
     */
    public function getDialect(): string;

    public function getTablePrefix(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function selectAll(string $sql, array $params = []): array;

    /**
     * Execute a statement (DDL/DML).
     */
    public function execute(string $sql, array $params = []): void;

    /**
     * @return int|null
     */
    public function lastInsertId(): ?int;
}
