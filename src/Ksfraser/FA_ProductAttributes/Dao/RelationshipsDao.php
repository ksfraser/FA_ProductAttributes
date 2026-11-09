<?php
declare(strict_types=1);

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Label resolution and option lists for related records via the shared
 * EntityRegistry.
 *
 * Queries the owning table of each entity type (customer, supplier, item,
 * invoice, contact, …) to resolve a primary key to its display label, or to
 * list id=>label options for a type's picker. All SQL goes through
 * DbAdapterInterface (named params -> native FA db_* calls at runtime).
 *
 * Table/column identifiers come from the trusted EntityRegistry, not user
 * input, so they are interpolated via sprintf placeholders rather than bound
 * params (bound params only work for values, not identifiers).
 *
 * @since 1.2.0
 */
class RelationshipsDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Resolve a primary key to its display label from the owning table.
     *
     * @param string $table   Table name without the company prefix
     * @param string $pk      Primary key column name
     * @param string $labelCol Label column name
     * @param string $id      Primary key value
     * @return string|null Label, or null when no row matches
     * @since 1.2.0
     */
    public function resolveLabel(string $table, string $pk, string $labelCol, string $id): ?string
    {
        $sql = sprintf(
            'SELECT `%s` AS label FROM `%s` WHERE `%s` = :id LIMIT 1',
            $labelCol,
            $table,
            $pk
        );

        $rows = $this->db->query($sql, ['id' => $id]);

        foreach ($rows as $row) {
            if (!empty($row['label'])) {
                return (string) $row['label'];
            }
        }

        return null;
    }

    /**
     * List id => label options for one entity type's owning table.
     *
     * @param string $table   Table name without the company prefix
     * @param string $pk      Primary key column name
     * @param string $labelCol Label column name
     * @return array<string, string> id => label
     * @since 1.2.0
     */
    public function listOptions(string $table, string $pk, string $labelCol): array
    {
        $sql = sprintf(
            'SELECT `%s`, `%s` FROM `%s` ORDER BY `%s`',
            $pk,
            $labelCol,
            $table,
            $labelCol
        );

        $options = array();
        foreach ($this->db->query($sql, []) as $row) {
            $key   = (string) ($row[$pk] ?? '');
            $label = (string) ($row[$labelCol] ?? '');
            if ($key !== '' && $label !== '') {
                $options[$key] = $label;
            }
        }

        return $options;
    }
}
