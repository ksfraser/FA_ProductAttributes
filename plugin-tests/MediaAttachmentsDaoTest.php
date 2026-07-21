<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\MediaAttachmentsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class MediaAttachmentsDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var MediaAttachmentsDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->dao = new MediaAttachmentsDao($this->db);
    }

    public function testListByStockIdQueriesCorrectly(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('SELECT * FROM `0_product_media_attachments`'),
                $this->callback(function ($p) {
                    return $p['stock_id'] === 'SKU001';
                })
            )
            ->willReturn([]);

        $this->assertSame([], $this->dao->listByStockId('SKU001'));
    }

    public function testAddInsertsAndReturnsId(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->stringContains('INSERT INTO'));
        $this->db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(10);

        $id = $this->dao->add('SKU001', 'https://example.com', 'Product video');
        $this->assertSame(10, $id);
    }

    public function testAddConvertsEmptyDescriptionToNull(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->callback(function ($p) {
                return $p['description'] === null;
            }));
        $this->db->method('lastInsertId')->willReturn(1);

        $this->dao->add('SKU001', 'https://example.com', '');
    }

    public function testDeleteById(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                'DELETE FROM `0_product_media_attachments` WHERE id = :id',
                ['id' => 5]
            );

        $this->dao->delete(5);
    }

    public function testDeleteByStockId(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                'DELETE FROM `0_product_media_attachments` WHERE stock_id = :stock_id',
                ['stock_id' => 'SKU001']
            );

        $this->dao->deleteByStockId('SKU001');
    }
}
