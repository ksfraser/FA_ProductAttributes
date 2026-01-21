<?php

namespace Ksfraser\FA_ProductAttributes\Db;

use Ksfraser\FA_ProductAttributes\Debug\DisplaySql;

final class FrontAccountingDbAdapter implements DbAdapterInterface
{
    /** @var string */
    private $prefix;

    /** @var string */
    private $driver;

    /** @var resource|\PDO|null */
    private $connection;

    /**
     * Constructor for FrontAccounting database adapter
     *
     * @param string|null $prefix Custom table prefix, or null to use company-based prefix
     * @param string $driver Database driver: 'fa' (default, uses FA db functions), 'pdo', or 'mysql'
     */
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
        else
        {
            $this->prefix = TB_PREF;
            return;
        }

        // Use company-based prefix if session is available, otherwise default to '0_'
        if (isset($_SESSION['wa_current_user']->company)) {
            $this->prefix = $_SESSION['wa_current_user']->company . '_';
        } else {
            $this->prefix = '0_';
        }
    }

    /**
     * Get the table prefix used by this adapter
     *
     * @return string The table prefix (e.g., '0_')
     */
    public function getTablePrefix(): string
    {
        return $this->prefix;
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
        if ($this->driver === 'fa') {
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

    /**
     * Execute a write query (INSERT, UPDATE, DELETE) that doesn't return results
     *
     * @param string $sql The SQL query with optional named parameters (e.g., :param)
     * @param array $params Associative array of parameter values
     * @return void
     */
    public function execute(string $sql, array $params = []): void
    {
        if ($this->driver === 'fa') {
            $sql = $this->bindParams($sql, $params);
            display_notification("Executing SQL: " . $sql);
            $result = db_query($sql);
            if ($result === false) {
                global $db_error;
                $error_msg = isset($db_error) ? $db_error : 'Unknown database error';
                throw new \Exception("Database execute failed: " . $error_msg);
            }
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

    /**
     * Get the ID of the last inserted row
     *
     * @return int|null The last insert ID, or null if not available
     */
    public function lastInsertId(): ?int
    {
        if ($this->driver === 'fa') {
            $res = db_query('SELECT LAST_INSERT_ID() AS id');
            if ($res === false) {
                return null;
            }
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
