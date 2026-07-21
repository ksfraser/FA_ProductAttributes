<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductWarrantyDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductWarrantyDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->dao = new ProductWarrantyDao($this->db);
    }

    public function testGetReturnsNullWhenNoRows(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                'SELECT * FROM `0_product_warranty` WHERE stock_id = :stock_id',
                ['stock_id' => 'NONEXISTENT']
            )
            ->willReturn([]);

        $result = $this->dao->get('NONEXISTENT');
        $this->assertNull($result);
    }

    public function testGetReturnsRow(): void
    {
        $row = ['stock_id' => 'SKU001', 'warranty_type' => 'manufacturer'];
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([$row]);

        $result = $this->dao->get('SKU001');
        $this->assertSame($row, $result);
    }

    public function testUpsertInsertsWhenNoExisting(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                'SELECT stock_id FROM `0_product_warranty` WHERE stock_id = :stock_id',
                ['stock_id' => 'SKU001']
            )
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($params) {
                    return isset($params['stock_id']) && $params['stock_id'] === 'SKU001';
                })
            );

        $this->dao->upsert('SKU001', ['warranty_type' => 'manufacturer']);
    }

    public function testUpsertUpdatesWhenExisting(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['stock_id' => 'SKU001']]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->callback(function ($params) {
                    return isset($params['stock_id']) && $params['stock_id'] === 'SKU001';
                })
            );

        $this->dao->upsert('SKU001', ['warranty_type' => 'extended']);
    }

    public function testUpsertFiltersToValidColumns(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->anything(),
                $this->callback(function ($params) {
                    return !isset($params['invalid_col']) && isset($params['warranty_type']);
                })
            );

        $this->dao->upsert('SKU001', ['warranty_type' => 'none', 'invalid_col' => 'x']);
    }

    public function testDeleteExecutesQuery(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                'DELETE FROM `0_product_warranty` WHERE stock_id = :stock_id',
                ['stock_id' => 'SKU001']
            );

        $this->dao->delete('SKU001');
    }

    public function testFilterDataDefaultsInvalidTypeToNone(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->callback(function ($p) {
                return $p['warranty_type'] === 'none';
            }));

        $this->dao->upsert('SKU001', ['warranty_type' => 'bogus']);
    }

    public function testFilterDataDefaultsInvalidUnitToMonths(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->callback(function ($p) {
                return $p['manufacturer_duration_unit'] === 'months';
            }));

        $this->dao->upsert('SKU001', ['warranty_type' => 'none', 'manufacturer_duration_unit' => 'bogus']);
    }

    public function testFilterDataNullsEmptyNumericFields(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->callback(function ($p) {
                return array_key_exists('manufacturer_duration', $p) && $p['manufacturer_duration'] === null;
            }));

        $this->dao->upsert('SKU001', ['warranty_type' => 'none', 'manufacturer_duration' => '']);
    }

    public function testFilterDataNullsEmptyStringFields(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);
        $this->db->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->callback(function ($p) {
                return array_key_exists('lifetime_notes', $p) && $p['lifetime_notes'] === null;
            }));

        $this->dao->upsert('SKU001', ['warranty_type' => 'none', 'lifetime_notes' => '']);
    }
}
