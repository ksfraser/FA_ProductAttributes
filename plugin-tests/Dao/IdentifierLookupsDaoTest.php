<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class IdentifierLookupsDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var IdentifierLookupsDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->dao = new IdentifierLookupsDao($this->db);
    }

    public function testListByTypeReturnsEmptyArrayWhenNoEntries(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->listByType('brand');

        $this->assertSame([], $result);
    }

    public function testListByTypeReturnsRows(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Acme'],
            ['id' => 2, 'name' => 'Beta'],
        ];
        $this->db->method('query')->willReturn($rows);

        $result = $this->dao->listByType('brand');

        $this->assertCount(2, $result);
        $this->assertSame('Acme', $result[0]['name']);
    }

    public function testListByTypeUsesParameterizedQuery(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('product_identifier_lookups'),
                $this->equalTo(['type' => 'manufacturer'])
            )
            ->willReturn([]);

        $this->dao->listByType('manufacturer');
    }

    public function testGetReturnsNullWhenNotFound(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->get(99);

        $this->assertNull($result);
    }

    public function testGetReturnsRow(): void
    {
        $row = ['id' => 1, 'type' => 'brand', 'name' => 'Acme'];
        $this->db->method('query')->willReturn([$row]);

        $result = $this->dao->get(1);

        $this->assertSame($row, $result);
    }

    public function testGetUsesParameterizedQuery(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('WHERE id = :id'),
                $this->equalTo(['id' => 5])
            )
            ->willReturn([]);

        $this->dao->get(5);
    }

    public function testAddInsertsAndReturnsLastInsertId(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($params) {
                    return $params['type'] === 'brand' && $params['name'] === 'Acme Corp';
                })
            );
        $this->db->method('lastInsertId')->willReturn(42);

        $result = $this->dao->add('brand', '  Acme Corp  ');

        $this->assertSame(42, $result);
    }

    public function testAddTrimsName(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->anything(),
                $this->callback(function ($params) {
                    return $params['name'] === 'Spaced Out';
                })
            );

        $this->dao->add('manufacturer', '  Spaced Out  ');
    }

    public function testDeleteExecutesDelete(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                $this->equalTo(['id' => 7])
            );

        $this->dao->delete(7);
    }

    public function testListTypesReturnsEmptyArray(): void
    {
        $this->db->method('query')->willReturn([]);

        $result = $this->dao->listTypes();

        $this->assertSame([], $result);
    }

    public function testListTypesReturnsDistinctValues(): void
    {
        $this->db->method('query')->willReturn([
            ['type' => 'brand'],
            ['type' => 'manufacturer'],
        ]);

        $result = $this->dao->listTypes();

        $this->assertSame(['brand', 'manufacturer'], $result);
    }

    public function testListTypesUsesDistinctQuery(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DISTINCT type'))
            ->willReturn([]);

        $this->dao->listTypes();
    }
}
