<?php

namespace Ksfraser\FA_ProductAttributes\Db;

use Ksfraser\FA_ProductAttributes\Debug\DisplaySql;

final class FrontAccountingDbAdapter implements DbAdapterInterface
{
    /** @var string */
    private $tablePrefix;

    public function __construct(?string $prefix = null)
    {
        $this->tablePrefix = $prefix ?? $this->getDefaultTablePrefix();
    }

    private function getDefaultTablePrefix(): string
    {
        // Use FA's table prefix logic
        if (isset($_SESSION['wa_current_user']->company)) {
            return $_SESSION['wa_current_user']->company . '_';
        }
        return '';
    }

    /**
     * Get the table prefix used by this adapter
     *
     * @return string The table prefix (e.g., '0_')
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Get the database dialect (SQL flavor) used by this adapter
     *
     * @return string The dialect name ('mysql' for MySQL/MariaDB)
     */
    public function getDialect(): string
    {
        // FrontAccounting runs against MySQL/MariaDB.
        return 'mysql';
    }

    /**
     * Execute a query and return all results as an array of associative arrays
     *
     * @param string $sql The SQL query with optional named parameters (e.g., :param)
     * @param array $params Associative array of parameter values
     * @return array Array of result rows, each as an associative array
     */
    public function query(string $sql, array $params = []): array
    {
        $sql = $this->bindParams($sql, $params);
        display_notification("Querying SQL: " . $sql);
        $res = db_query($sql);
        if ($res === false) {
            global $db_error;
            $error_msg = isset($db_error) ? $db_error : 'Unknown database error';
            throw new \Exception("Database query failed: " . $error_msg);
        }
        $rows = [];
        while ($row = db_fetch_assoc($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Execute a write query (INSERT, UPDATE, DELETE) that doesn't return results
     *
     * @param string $sql The SQL query with optional named parameters (e.g., :param)
     * @param array $params Associative array of parameter values
     * @return void
     */
    public function execute(string $sql, array $params = []): void
    {
        $sql = $this->bindParams($sql, $params);
        display_notification("Executing SQL: " . $sql);
        $result = db_query($sql);
        if ($result === false) {
            global $db_error;
            $error_msg = isset($db_error) ? $db_error : 'Unknown database error';
            throw new \Exception("Database execute failed: " . $error_msg);
        }
    }

    /**
     * Get the ID of the last inserted row
     *
     * @return int|null The last insert ID, or null if not available
     */
    public function lastInsertId(): ?int
    {
        $res = db_query('SELECT LAST_INSERT_ID() AS id');
        if ($res === false) {
            return null;
        }
        $row = db_fetch_assoc($res);
        if (!$row || !isset($row['id'])) {
            return null;
        }
        return (int)$row['id'];
    }

    private function bindParams(string $sql, array $params): string
    {
        foreach ($params as $k => $v) {
            $name = is_string($k) ? $k : (string)$k;
            if ($name === '') {
                continue;
            }
            if ($name[0] !== ':') {
                $name = ':' . $name;
            }

            if ($v === null) {
                $replacement = 'NULL';
            } elseif (is_numeric($v)) {
                $replacement = (string)$v;
            } else {
                $replacement = "'" . addslashes((string)$v) . "'";
            }

            $sql = str_replace($name, $replacement, $sql);
        }
        return $sql;
    }
}
