<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductShippingClassesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductShippingClassesDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductShippingClassesDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductShippingClassesDao($this->db);
    }

    public function testListReturnsRows(): void
    {
        $rows = [['id' => 1, 'name' => 'Hazardous', 'slug' => 'hazardous']];
        $this->db->method('query')->willReturn($rows);

        $this->assertSame($rows, $this->dao->list());
    }

    public function testGetReturnsRow(): void
    {
        $row = ['id' => 1, 'name' => 'Hazardous', 'slug' => 'hazardous'];
        $this->db->method('query')->willReturn([$row]);

        $this->assertSame($row, $this->dao->get(1));
    }

    public function testGetReturnsNullWhenMissing(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->assertNull($this->dao->get(99));
    }

    public function testGetBySlugReturnsRow(): void
    {
        $row = ['id' => 1, 'name' => 'Hazardous', 'slug' => 'hazardous'];
        $this->db->method('query')->willReturn([$row]);

        $this->assertSame($row, $this->dao->getBySlug('hazardous'));
    }

    public function testUpsertInserts(): void
    {
        $this->db->method('lastInsertId')->willReturn(7);
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->arrayHasKey('slug')
            );

        $id = $this->dao->upsert('Hazardous', 'hazardous', 'Flammable goods', 1, true);

        $this->assertSame(7, $id);
    }

    public function testUpsertUpdates(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('id')
            );

        $id = $this->dao->upsert('Hazardous', 'hazardous', 'Flammable goods', 1, true, 3);

        $this->assertSame(3, $id);
    }

    public function testDeleteClearsReferencesThenDeletes(): void
    {
        $this->db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->stringContains('UPDATE'),
                    ['shipping_class_id' => 3],
                ],
                [
                    $this->stringContains('DELETE FROM'),
                    ['id' => 3],
                ]
            );

        $this->dao->delete(3);
    }
}
