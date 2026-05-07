<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductMediaDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductMediaDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductMediaDao($this->db);
    }

    // ── getProductMedia() ─────────────────────────────────────────────────────

    public function testGetProductMediaReturnsEmptyArrayWhenNone(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->getProductMedia('SKU001');

        $this->assertSame([], $result);
    }

    public function testGetProductMediaOrdersByQuery(): void
    {
        $rows = [
            ['id' => 1, 'stock_id' => 'SKU001', 'url' => 'https://example.com/img.jpg', 'sort_order' => 0],
        ];
        $this->db->method('query')->willReturn($rows);

        $result = $this->dao->getProductMedia('SKU001');

        $this->assertCount(1, $result);
        $this->assertSame('https://example.com/img.jpg', $result[0]['url']);
    }

    // ── getMediaItem() ────────────────────────────────────────────────────────

    public function testGetMediaItemReturnsNullWhenNotFound(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->getMediaItem(99);

        $this->assertNull($result);
    }

    public function testGetMediaItemReturnsRow(): void
    {
        $row = ['id' => 5, 'stock_id' => 'SKU001', 'url' => 'https://example.com/img.jpg'];
        $this->db->method('query')->willReturn([$row]);

        $result = $this->dao->getMediaItem(5);

        $this->assertSame($row, $result);
    }

    // ── addMedia() ────────────────────────────────────────────────────────────

    public function testAddMediaReturnsLastInsertId(): void
    {
        $this->db->method('query')->willReturn([]);   // no existing primary
        $this->db->method('lastInsertId')->willReturn(42);

        $result = $this->dao->addMedia('SKU001', 'https://example.com/img.jpg', 'Alt', 0, 'image', false);

        $this->assertSame(42, $result);
    }

    public function testAddMediaClearsPreviousPrimaryWhenIsPrimaryTrue(): void
    {
        $sqlCalls = [];
        $this->db->expects($this->atLeast(2))
            ->method('execute')
            ->with(
                $this->callback(function ($sql) use (&$sqlCalls) {
                    $sqlCalls[] = $sql;
                    return true;
                }),
                $this->anything()
            );
        $this->db->method('lastInsertId')->willReturn(1);

        $this->dao->addMedia('SKU001', 'https://example.com/img.jpg', '', 0, 'image', true);

        // First execute should clear existing primary flag
        $foundClear = false;
        foreach ($sqlCalls as $sql) {
            if (strpos($sql, 'is_primary') !== false && strpos($sql, 'UPDATE') !== false) {
                $foundClear = true;
                break;
            }
        }
        $this->assertTrue($foundClear, 'Expected UPDATE to clear is_primary before insert');
    }

    // ── deleteMedia() ─────────────────────────────────────────────────────────

    public function testDeleteMediaDeletesVariationLinksFirst(): void
    {
        $calls = [];
        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->with(
                $this->callback(function ($sql) use (&$calls) {
                    $calls[] = $sql;
                    return true;
                }),
                $this->anything()
            );

        $this->dao->deleteMedia(5);

        $this->assertStringContainsString('product_media_variation_links', $calls[0]);
        $this->assertStringContainsString('product_media', $calls[1]);
    }

    // ── getVariationLinks() ───────────────────────────────────────────────────

    public function testGetVariationLinksReturnsEmptyArrayWhenNone(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->getVariationLinks(5);

        $this->assertSame([], $result);
    }

    public function testGetVariationLinksReturnsStockIds(): void
    {
        $this->db->method('query')->willReturn([
            ['variation_stock_id' => 'PARENT-RED'],
            ['variation_stock_id' => 'PARENT-BLUE'],
        ]);

        $result = $this->dao->getVariationLinks(5);

        $this->assertSame(['PARENT-RED', 'PARENT-BLUE'], $result);
    }

    // ── setVariationLinks() ───────────────────────────────────────────────────

    public function testSetVariationLinksDeletesThenInserts(): void
    {
        $calls = [];
        $this->db->expects($this->atLeast(2))
            ->method('execute')
            ->with(
                $this->callback(function ($sql) use (&$calls) {
                    $calls[] = $sql;
                    return true;
                }),
                $this->anything()
            );

        $this->dao->setVariationLinks(5, ['PARENT-RED', 'PARENT-BLUE']);

        $this->assertStringContainsString('DELETE', $calls[0]);
    }

    public function testSetVariationLinksOnlyDeletesWhenEmptyArray(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('DELETE'), $this->anything());

        $this->dao->setVariationLinks(5, []);
    }
}
