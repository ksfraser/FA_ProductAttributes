<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Simple URL-based media attachments for a product.
 *
 * YouTube videos, images, documents — just a list of URLs with descriptions.
 */
class MediaAttachmentsDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByStockId(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_media_attachments`'
            . ' WHERE stock_id = :stock_id'
            . ' ORDER BY created_date DESC, id DESC',
            ['stock_id' => $stockId]
        );
    }

    public function add(string $stockId, string $url, ?string $description): int
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'INSERT INTO `' . $p . 'product_media_attachments`'
            . ' (stock_id, url, description)'
            . ' VALUES (:stock_id, :url, :description)',
            [
                'stock_id'    => $stockId,
                'url'         => $url,
                'description' => $description !== null && $description !== '' ? $description : null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_media_attachments` WHERE id = :id',
            ['id' => $id]
        );
    }

    public function deleteByStockId(string $stockId): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_media_attachments` WHERE stock_id = :stock_id',
            ['stock_id' => $stockId]
        );
    }
}
