<?php

namespace Ksfraser\ModulesDAO\Db;

use PDO;

final class PdoDbAdapter implements DbAdapterInterface
{
    /** @var PDO */
    private $pdo;
    /** @var string */
    private $tablePrefix;

    /**
     * Constructor - accepts either a PDO instance or creates one automatically
     */
    public function __construct($pdo = null, ?string $prefix = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
            $this->tablePrefix = $prefix ?? '';
        } else {
            // Auto-create PDO connection using FA's database config
            $this->tablePrefix = $pdo ?? $this->getDefaultTablePrefix(); // $pdo parameter is actually prefix when not PDO
            $this->pdo = $this->createPdoConnection();
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function getDefaultTablePrefix(): string
    {
        if (isset($_SESSION['wa_current_user']->company)) {
            return $_SESSION['wa_current_user']->company . '_';
        }
        return '';
    }

    private function createPdoConnection(): PDO
    {
        global $db_connections;
        $company = $_SESSION['wa_current_user']->company ?? 0;

        return new PDO(
            "mysql:host={$db_connections[$company]['host']};dbname={$db_connections[$company]['name']}",
            $db_connections[$company]['user'],
            $db_connections[$company]['password']
        );
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getDialect(): string
    {
        $name = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return strtolower($name);
    }

    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception("Database query failed: " . $e->getMessage());
        }
    }

    public function execute(string $sql, array $params = []): void
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new \Exception("Database execute failed: " . $e->getMessage());
        }
    }

    public function lastInsertId(): ?int
    {
        try {
            $id = $this->pdo->lastInsertId();
            return $id ? (int)$id : null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}