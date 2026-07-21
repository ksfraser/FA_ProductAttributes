<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\CategoriesApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class CategoriesApiControllerTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var CategoriesApiController */
    private $controller;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->controller = new CategoriesApiController($this->dao, $this->db, true);
    }

    public function testIndex(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('"categories"', $output);
    }

    public function testShowFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'test', 'label' => 'Test']]);

        ob_start();
        $this->controller->show(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"category"', $output);
    }

    public function testShowNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        ob_start();
        $this->controller->show(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Category not found"', $output);
    }

    public function testDeleteNoUsage(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'test', 'label' => 'Test', 'description' => '', 'sort_order' => 0]]);
        $this->dao->expects($this->once())
            ->method('upsertCategory');
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 0]]);

        ob_start();
        $this->controller->delete(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"message"', $output);
    }

    public function testDeleteNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        ob_start();
        $this->controller->delete(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Category not found"', $output);
    }
}
