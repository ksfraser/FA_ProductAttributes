<?php

namespace Ksfraser\FA_ProductAttributes\Db;

final class FrontAccountingDbAdapter implements DbAdapterInterface
{
    /** @var string */
    private $prefix;

    public function __construct(?string $prefix = null)
    {
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
        $sql = $this->bindParams($sql, $params);
        $res = db_query($sql);
        $rows = [];
        while ($row = db_fetch_assoc($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function execute(string $sql, array $params = []): void
    {
        $sql = $this->bindParams($sql, $params);
        db_query($sql);
    }

    public function lastInsertId(): ?int
    {
        $res = db_query('SELECT LAST_INSERT_ID() AS id');
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
            } else {
                $replacement = "'" . db_escape((string)$v) . "'";
            }

            $sql = str_replace($name, $replacement, $sql);
        }
        return $sql;
    }
}
