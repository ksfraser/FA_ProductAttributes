<?php

namespace Ksfraser\ModulesDAO\Db;

/**
 * Database adapter that uses direct mysql_ functions
 */
class MysqlDbAdapter implements DbAdapterInterface
{
    /** @var resource */
    private $connection;
    /** @var string */
    private $tablePrefix;

    public function __construct(?string $prefix = null)
    {
        $this->tablePrefix = $prefix ?? '';

        // Initialize mysql connection using FA's database config
        global $db_connections;
        $company = $_SESSION['wa_current_user']->company ?? 0;

        $this->connection = mysql_connect(
            $db_connections[$company]['host'],
            $db_connections[$company]['user'],
            $db_connections[$company]['password']
        );
        if (!$this->connection) {
            throw new \Exception("Failed to connect to database: " . mysql_error());
        }
        if (!mysql_select_db($db_connections[$company]['name'], $this->connection)) {
            throw new \Exception("Failed to select database: " . mysql_error($this->connection));
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            mysql_close($this->connection);
        }
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
        $sql = $this->bindParams($sql, $params);
        $result = mysql_query($sql, $this->connection);
        if (!$result) {
            throw new \Exception("DB select error: " . mysql_error($this->connection));
        }
        $rows = [];
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function execute(string $sql, array $params = []): void
    {
        $sql = $this->bindParams($sql, $params);
        $result = mysql_query($sql, $this->connection);
        if (!$result) {
            throw new \Exception("DB execute error: " . mysql_error($this->connection));
        }
    }

    public function lastInsertId(): ?int
    {
        return mysql_insert_id($this->connection);
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
                $replacement = "'" . mysql_real_escape_string((string)$v, $this->connection) . "'";
            }

            $sql = str_replace($name, $replacement, $sql);
        }
        return $sql;
    }
}