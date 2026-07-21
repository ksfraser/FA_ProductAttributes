<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\ValuesApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ValuesApiControllerTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ValuesApiController */
    private $controller;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->controller = new ValuesApiController($this->dao, $this->db, true);
    }

    public function testIndex(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->index(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"values"', $output);
    }

    public function testShowFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 5, 'value' => 'Red', 'slug' => 'red']]);

        ob_start();
        $this->controller->show(1, 5);
        $output = ob_get_clean();

        $this->assertStringContainsString('"value"', $output);
    }

    public function testShowNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->show(1, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Value not found"', $output);
    }

    public function testDeleteNoUsage(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 5, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0]]);
        $this->dao->expects($this->once())
            ->method('upsertValue');
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 0]]);

        ob_start();
        $this->controller->delete(1, 5);
        $output = ob_get_clean();

        $this->assertStringContainsString('"message"', $output);
    }

    public function testDeleteNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->delete(1, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Value not found"', $output);
    }
}
