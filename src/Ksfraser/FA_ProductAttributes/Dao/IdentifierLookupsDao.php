<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: CRUD for the product_identifier_lookups table.
 *
 * Provides Brand and Manufacturer dropdown data.
 * The 'type' column distinguishes between 'brand' and 'manufacturer' entries.
 */
class IdentifierLookupsDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * List all lookup entries of a given type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByType(string $type): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT id, name FROM `' . $p . 'product_identifier_lookups`'
            . ' WHERE type = :type ORDER BY name',
            ['type' => $type]
        );
    }

    /**
     * Get a single lookup entry by ID.
     *
     * @return array<string, mixed>|null
     */
    public function get(int $id): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT id, type, name FROM `' . $p . 'product_identifier_lookups` WHERE id = :id',
            ['id' => $id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Add a new lookup entry. Returns the new ID.
     */
    public function add(string $type, string $name): int
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_identifier_lookups` (type, name) VALUES (:type, :name)',
            ['type' => $type, 'name' => trim($name)]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing lookup entry's name.
     */
    public function update(int $id, string $name): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'UPDATE `' . $p . 'product_identifier_lookups` SET name = :name WHERE id = :id',
            ['name' => trim($name), 'id' => $id]
        );
    }

    /**
     * Delete a lookup entry by ID.
     */
    public function delete(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_identifier_lookups` WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Get all distinct types in the table.
     *
     * @return string[]
     */
    public function listTypes(): array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT DISTINCT type FROM `' . $p . 'product_identifier_lookups` ORDER BY type'
        );
        return array_column($rows, 'type');
    }
}
