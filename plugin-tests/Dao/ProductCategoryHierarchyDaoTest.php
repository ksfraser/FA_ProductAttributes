<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductCategoryHierarchyDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductCategoryHierarchyDaoTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ProductCategoryHierarchyDao */
    private $dao;

    protected function setUp(): void
    {
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('fa_');
        $this->dao = new ProductCategoryHierarchyDao($this->db);
    }

    public function testGetParentReturnsNullWhenNoMapping(): void
    {
        $this->db->method('query')->willReturn([]);

        $this->assertNull($this->dao->getParent(10));
    }

    public function testGetParentReturnsParentId(): void
    {
        $this->db->method('query')->willReturn([['parent_category_id' => '3']]);

        $this->assertSame(3, $this->dao->getParent(10));
    }

    public function testSetParentUpsertsMapping(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('ON DUPLICATE KEY UPDATE'),
                ['category_id' => 10, 'parent_category_id' => 3, 'parent_category_id2' => 3]
            );

        $this->dao->setParent(10, 3);
    }

    public function testSetParentWithNullDeletesMapping(): void
    {
        $this->db->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('DELETE FROM'),
                ['category_id' => 10]
            );

        $this->dao->setParent(10, null);
    }

    public function testListChildrenReturnsCategoryIds(): void
    {
        $rows = [['category_id' => '10'], ['category_id' => '11']];
        $this->db->method('query')->willReturn($rows);

        $result = $this->dao->listChildren(3);

        $this->assertSame([10, 11], $result);
    }
}
