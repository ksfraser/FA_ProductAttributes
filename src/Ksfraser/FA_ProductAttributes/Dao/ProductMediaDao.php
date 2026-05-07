<?php

namespace Ksfraser\FA_ProductAttributes\Dao;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Manages product media (images/videos/documents) and
 * their variation-scope assignments.
 *
 * Tables:
 *   0_product_media                  — media items (one row per file/URL)
 *   0_product_media_variation_links  — which variations each item applies to
 */
class ProductMediaDao
{
    /** @var DbAdapterInterface */
    private $db;

    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    // ── Media CRUD ────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>  Ordered by sort_order ASC, id ASC
     */
    public function getProductMedia(string $stockId): array
    {
        $p = $this->db->getTablePrefix();
        return $this->db->query(
            'SELECT * FROM `' . $p . 'product_media`'
            . ' WHERE stock_id = :stock_id'
            . ' ORDER BY sort_order ASC, id ASC',
            ['stock_id' => $stockId]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMediaItem(int $id): ?array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT * FROM `' . $p . 'product_media` WHERE id = :id',
            ['id' => $id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Insert a new media item and return its new auto-increment ID.
     */
    public function addMedia(
        string $stockId,
        string $url,
        string $altText,
        int $sortOrder,
        string $mediaType,
        bool $isPrimary
    ): int {
        $p         = $this->db->getTablePrefix();
        $validTypes = ['image', 'video', 'document'];
        $type      = in_array($mediaType, $validTypes, true) ? $mediaType : 'image';

        if ($isPrimary) {
            // Clear existing primary flag for this product before setting new one
            $this->db->execute(
                'UPDATE `' . $p . 'product_media` SET is_primary = 0 WHERE stock_id = :stock_id',
                ['stock_id' => $stockId]
            );
        }

        $this->db->execute(
            'INSERT INTO `' . $p . 'product_media`'
            . ' (stock_id, url, alt_text, sort_order, media_type, is_primary)'
            . ' VALUES (:stock_id, :url, :alt_text, :sort_order, :media_type, :is_primary)',
            [
                'stock_id'   => $stockId,
                'url'        => $url,
                'alt_text'   => $altText !== '' ? $altText : null,
                'sort_order' => $sortOrder,
                'media_type' => $type,
                'is_primary' => $isPrimary ? 1 : 0,
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data  May contain: url, alt_text, sort_order, is_primary
     */
    public function updateMedia(int $id, array $data): void
    {
        $p       = $this->db->getTablePrefix();
        $allowed = ['url', 'alt_text', 'sort_order', 'is_primary'];
        $sets    = [];
        $bound   = ['id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]      = '`' . $col . '` = :' . $col;
                $bound[$col] = $data[$col];
            }
        }

        if (empty($sets)) {
            return;
        }

        $this->db->execute(
            'UPDATE `' . $p . 'product_media` SET ' . implode(', ', $sets) . ' WHERE id = :id',
            $bound
        );
    }

    public function deleteMedia(int $id): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_media_variation_links` WHERE media_id = :id',
            ['id' => $id]
        );
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_media` WHERE id = :id',
            ['id' => $id]
        );
    }

    // ── Variation links ───────────────────────────────────────────────────────

    /**
     * @return string[]  Stock IDs of variations this media item applies to.
     *                   Empty array = applies to all (no scoping).
     */
    public function getVariationLinks(int $mediaId): array
    {
        $p    = $this->db->getTablePrefix();
        $rows = $this->db->query(
            'SELECT variation_stock_id FROM `' . $p . 'product_media_variation_links`'
            . ' WHERE media_id = :media_id',
            ['media_id' => $mediaId]
        );
        return array_column($rows, 'variation_stock_id');
    }

    /**
     * Replace all variation-scope links for a media item.
     *
     * @param string[] $variationStockIds  Empty array clears scoping (applies to all).
     */
    public function setVariationLinks(int $mediaId, array $variationStockIds): void
    {
        $p = $this->db->getTablePrefix();
        $this->db->execute(
            'DELETE FROM `' . $p . 'product_media_variation_links` WHERE media_id = :media_id',
            ['media_id' => $mediaId]
        );
        foreach ($variationStockIds as $varId) {
            $varId = trim((string)$varId);
            if ($varId !== '') {
                $this->db->execute(
                    'INSERT INTO `' . $p . 'product_media_variation_links`'
                    . ' (media_id, variation_stock_id) VALUES (:media_id, :variation_stock_id)',
                    ['media_id' => $mediaId, 'variation_stock_id' => $varId]
                );
            }
        }
    }
}
