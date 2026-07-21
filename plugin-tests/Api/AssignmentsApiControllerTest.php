<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\AssignmentsApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class AssignmentsApiControllerTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var AssignmentsApiController */
    private $controller;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->controller = new AssignmentsApiController($this->dao, $this->db, true);
    }

    public function testIndex(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->controller->index('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('"assignments"', $output);
    }

    public function testShowFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([['id' => 5, 'category_id' => 1]]);

        ob_start();
        $this->controller->show('SKU001', 5);
        $output = ob_get_clean();

        $this->assertStringContainsString('"assignment"', $output);
    }

    public function testShowNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->controller->show('SKU001', 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Assignment not found"', $output);
    }

    public function testDeleteFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([['id' => 5]]);
        $this->dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(5);

        ob_start();
        $this->controller->delete('SKU001', 5);
        $output = ob_get_clean();

        $this->assertStringContainsString('"message":"Assignment deleted"', $output);
    }

    public function testDeleteNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->controller->delete('SKU001', 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Assignment not found"', $output);
    }
}
