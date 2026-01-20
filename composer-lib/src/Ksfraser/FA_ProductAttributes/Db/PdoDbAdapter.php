<?php

namespace Ksfraser\FA_ProductAttributes\Db;

use PDO;

final class PdoDbAdapter implements DbAdapterInterface
{
    /** @var PDO */
    private $pdo;

    /** @var string */
    private $prefix;

    public function __construct(PDO $pdo, string $prefix = '')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getTablePrefix(): string
    {
        return $this->prefix;
    }

    public function getDialect(): string
    {
        $name = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return strtolower($name);
    }

    public function selectAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function execute(string $sql, array $params = []): void
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function lastInsertId(): ?int
    {
        $id = $this->pdo->lastInsertId();
        if ($id === false || $id === '' || $id === '0') {
            return null;
        }
        return (int)$id;
    }
}
