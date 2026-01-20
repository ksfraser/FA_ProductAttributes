<?php

namespace Ksfraser\FA_ProductAttributes\Db;

final class FrontAccountingDbAdapter implements DbAdapterInterface
{
    /** @var string */
    private $prefix;

    /** @var string */
    private $driver;

    /** @var resource|\PDO|null */
    private $connection;

    public function __construct(?string $prefix = null, string $driver = 'fa')
    {
        $this->driver = $driver;

        if ($driver === 'pdo' || $driver === 'mysql') {
            global $db_connections;
            $company = $_SESSION['wa_current_user']->company ?? 0;

            if ($driver === 'pdo') {
                try {
                    $this->connection = new \PDO(
                        "mysql:host={$db_connections[$company]['host']};dbname={$db_connections[$company]['name']}",
                        $db_connections[$company]['user'],
                        $db_connections[$company]['password']
                    );
                    $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                } catch (\PDOException $e) {
                    throw new \Exception("DB connection failed: " . $e->getMessage());
                }
            } else { // mysql
                $this->connection = mysql_connect(
                    $db_connections[$company]['host'],
                    $db_connections[$company]['user'],
                    $db_connections[$company]['password']
                );
                if (!$this->connection) {
                    throw new \Exception("Failed to connect to database");
                }
                if (!mysql_select_db($db_connections[$company]['name'], $this->connection)) {
                    throw new \Exception("Failed to select database");
                }
            }
        }

        if ($prefix !== null) {
            $this->prefix = $prefix;
            return;
        }

        // Use company-based prefix if session is available, otherwise default to '0_'
        if (isset($_SESSION['wa_current_user']->company)) {
            $this->prefix = $_SESSION['wa_current_user']->company . '_';
        } else {
            $this->prefix = '0_';
        }
    }

    public function getTablePrefix(): string
    {
        return $this->prefix;
    }

    public function getDialect(): string
    {
        // FrontAccounting runs against MySQL/MariaDB.
        return 'mysql';
    }

    public function selectAll(string $sql, array $params = []): array
    {
        if ($this->driver === 'fa') {
            $sql = $this->bindParams($sql, $params);
            $res = db_query($sql);
            $rows = [];
            while ($row = db_fetch_assoc($res)) {
                $rows[] = $row;
            }
            return $rows;
        } elseif ($this->driver === 'pdo') {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else { // mysql
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
    }

    public function execute(string $sql, array $params = []): void
    {
        if ($this->driver === 'fa') {
            $sql = $this->bindParams($sql, $params);
            db_query($sql);
        } elseif ($this->driver === 'pdo') {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
        } else { // mysql
            $sql = $this->bindParams($sql, $params);
            $result = mysql_query($sql, $this->connection);
            if (!$result) {
                throw new \Exception("DB execute error: " . mysql_error($this->connection));
            }
        }
    }

    public function lastInsertId(): ?int
    {
        if ($this->driver === 'fa') {
            $res = db_query('SELECT LAST_INSERT_ID() AS id');
            $row = db_fetch_assoc($res);
            if (!$row || !isset($row['id'])) {
                return null;
            }
            return (int)$row['id'];
        } elseif ($this->driver === 'pdo') {
            return (int)$this->connection->lastInsertId();
        } else { // mysql
            return mysql_insert_id($this->connection);
        }
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
            } else {
                $replacement = "'" . db_escape((string)$v) . "'";
            }

            $sql = str_replace($name, $replacement, $sql);
        }
        return $sql;
    }
}
