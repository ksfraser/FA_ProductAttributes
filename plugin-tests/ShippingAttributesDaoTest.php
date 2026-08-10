<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ShippingAttributesDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ShippingAttributesDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ShippingAttributesDao($this->db);
    }

    // ── get() ─────────────────────────────────────────────────────────────────

    public function testGetReturnsNullWhenNoRecord(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->get('SKU001');

        $this->assertNull($result);
    }

    public function testGetReturnsRowWhenRecordExists(): void
    {
        $row = [
            'stock_id' => 'SKU001',
            'length'   => '30.000',
            'width'    => '20.000',
            'height'   => '10.000',
            'weight'   => '1.500',
        ];
        $this->db->method('query')->willReturn([$row]);

        $result = $this->dao->get('SKU001');

        $this->assertSame($row, $result);
    }

    // ── upsert() – INSERT path ────────────────────────────────────────────────

    public function testUpsertExecutesInsertWhenNoExistingRecord(): void
    {
        // query() for existence check returns empty → INSERT path
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->upsert('SKU001', ['length' => 30.0, 'weight' => 1.5]);
    }

    public function testUpsertExecutesUpdateWhenRecordExists(): void
    {
        // query() returns existing row → UPDATE path
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['stock_id' => 'SKU001']]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('stock_id')
            );

        $this->dao->upsert('SKU001', ['length' => 35.0]);
    }

    public function testUpsertIgnoresUnknownKeys(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);

        // The execute call should NOT contain 'unknown_field' in the SQL
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->logicalNot($this->stringContains('unknown_field')),
                $this->anything()
            );

        $this->dao->upsert('SKU001', ['length' => 10.0, 'unknown_field' => 'bad']);
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDeleteExecutesDeleteStatement(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['stock_id' => 'SKU001']
            );

        $this->dao->delete('SKU001');
    }

    // ── full round-trip data ──────────────────────────────────────────────────

    public function testUpsertPassesAllAllowedColumns(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);

        $fullData = [
            'length'                => 30.5,
            'width'                 => 20.5,
            'height'                => 10.5,
            'dim_unit'              => 'cm',
            'weight'                => 1.5,
            'weight_unit'           => 'kg',
            'is_hazardous'          => 1,
            'hazmat_class'          => '3',
            'un_number'             => '1234',
            'proper_shipping_name'  => 'Flammable liquids',
            'packing_group'         => 'II',
            'is_fragile'            => 1,
            'is_stackable'          => 0,
            'is_oversize'           => 0,
            'is_perishable'         => 0,
            'temperature_sensitive' => 0,
            'temp_min'              => null,
            'temp_max'              => null,
            'temp_unit'             => 'C',
            'hs_code'               => '2710.12',
            'country_of_origin'     => 'Canada',
            'customs_description'   => 'Paint thinner',
            'declared_value'        => 25.00,
        ];

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($bound) use ($fullData) {
                    foreach (array_keys($fullData) as $col) {
                        if (!array_key_exists($col, $bound)) {
                            return false;
                        }
                    }
                    return true;
                })
            );

        $this->dao->upsert('SKU001', $fullData);
    }

    public function testUpsertPersistsShippingClassId(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($bound) {
                    return isset($bound['shipping_class_id']) && $bound['shipping_class_id'] === 4;
                })
            );

        $this->dao->upsert('SKU001', ['length' => 10.0, 'shipping_class_id' => 4]);
    }

    public function testUpsertIgnoresInvalidShippingClassIdValues(): void
    {
        $this->db->expects($this->once())->method('query')->willReturn([]);

        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->anything(),
                $this->callback(function ($bound) {
                    return array_key_exists('shipping_class_id', $bound)
                        && $bound['shipping_class_id'] === 0;
                })
            );

        $this->dao->upsert('SKU001', ['shipping_class_id' => 'abc']);
    }
}
