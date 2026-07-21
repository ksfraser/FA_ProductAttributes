<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\ApiRouter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ApiRouterTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var ApiRouter */
    private $router;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->db  = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->router = new ApiRouter($this->dao, $this->db, true);
    }

    public function testHandleUnknownResourceReturns404(): void
    {
        ob_start();
        $this->router->handle('GET', '/api/unknown');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Unknown resource"', $output);
    }

    public function testHandleEmptyPathReturns404(): void
    {
        ob_start();
        $this->router->handle('GET', '/api/');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Unknown resource"', $output);
    }

    public function testHandleCategoriesGet(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        ob_start();
        $this->router->handle('GET', '/api/categories');
        $output = ob_get_clean();

        $this->assertStringContainsString('"categories"', $output);
    }

    public function testHandleCategoriesGetById(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'test']]);

        ob_start();
        $this->router->handle('GET', '/api/categories/1');
        $output = ob_get_clean();

        $this->assertStringContainsString('"category"', $output);
    }

    public function testHandleCategoriesGetByIdNotFound(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([]);

        ob_start();
        $this->router->handle('GET', '/api/categories/999');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Category not found"', $output);
    }

    public function testHandleCategoriesPostMissingInput(): void
    {
        $this->dao->expects($this->never())->method('upsertCategory');

        ob_start();
        $this->router->handle('POST', '/api/categories');
        $output = ob_get_clean();

        $this->assertStringContainsString('Missing required fields: code, label', $output);
    }

    public function testHandleCategoriesPutMissingInput(): void
    {
        $this->dao->expects($this->never())->method('upsertCategory');

        ob_start();
        $this->router->handle('PUT', '/api/categories/1');
        $output = ob_get_clean();

        $this->assertStringContainsString('Missing required fields: code, label', $output);
    }

    public function testHandleCategoriesDelete(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'test', 'label' => 'Test', 'description' => '', 'sort_order' => 0]]);
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 0]]);
        $this->dao->expects($this->once())
            ->method('upsertCategory');

        ob_start();
        $this->router->handle('DELETE', '/api/categories/1');
        $output = ob_get_clean();

        $this->assertStringContainsString('"message"', $output);
    }

    public function testHandleCategoriesDeleteConflict(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'test', 'label' => 'Test']]);
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 3]]);

        ob_start();
        $this->router->handle('DELETE', '/api/categories/1');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Cannot delete category that is in use by products"', $output);
    }

    public function testHandleCategoriesMethodNotAllowed(): void
    {
        ob_start();
        $this->router->handle('PATCH', '/api/categories');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Method not allowed"', $output);
    }

    public function testHandleValuesGet(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->router->handle('GET', '/api/categories/1/values');
        $output = ob_get_clean();

        $this->assertStringContainsString('"values"', $output);
    }

    public function testHandleValuesGetById(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 5, 'value' => 'Red']]);

        ob_start();
        $this->router->handle('GET', '/api/categories/1/values/5');
        $output = ob_get_clean();

        $this->assertStringContainsString('"value"', $output);
    }

    public function testHandleValuesPostMissingInput(): void
    {
        $this->dao->expects($this->never())->method('upsertValue');

        ob_start();
        $this->router->handle('POST', '/api/categories/1/values');
        $output = ob_get_clean();

        $this->assertStringContainsString('Missing required fields: value, slug', $output);
    }

    public function testHandleValuesDelete(): void
    {
        $this->dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 5, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0]]);
        $this->db->expects($this->once())
            ->method('query')
            ->willReturn([['count' => 0]]);
        $this->dao->expects($this->once())
            ->method('upsertValue');

        ob_start();
        $this->router->handle('DELETE', '/api/categories/1/values/5');
        $output = ob_get_clean();

        $this->assertStringContainsString('"message"', $output);
    }

    public function testHandleAssignmentsGet(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([]);

        ob_start();
        $this->router->handle('GET', '/api/products/SKU001/assignments');
        $output = ob_get_clean();

        $this->assertStringContainsString('"assignments"', $output);
    }

    public function testHandleAssignmentsGetById(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([['id' => 10]]);

        ob_start();
        $this->router->handle('GET', '/api/products/SKU001/assignments/10');
        $output = ob_get_clean();

        $this->assertStringContainsString('"assignment"', $output);
    }

    public function testHandleAssignmentsDelete(): void
    {
        $this->dao->expects($this->once())
            ->method('listAssignments')
            ->with('SKU001')
            ->willReturn([['id' => 10]]);
        $this->dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(10);

        ob_start();
        $this->router->handle('DELETE', '/api/products/SKU001/assignments/10');
        $output = ob_get_clean();

        $this->assertStringContainsString('"message":"Assignment deleted"', $output);
    }

    public function testHandleAssignmentsMissingStockId(): void
    {
        ob_start();
        $this->router->handle('GET', '/api/products');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Missing stock_id"', $output);
    }

    public function testHandleProductsUnknownResource(): void
    {
        ob_start();
        $this->router->handle('GET', '/api/products/SKU001/unknown');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Unknown resource"', $output);
    }

    public function testHandleCategoriesWithValuesSuffixUnknown(): void
    {
        ob_start();
        $this->router->handle('GET', '/api/categories/1/values/5/extra');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Unknown resource"', $output);
    }

    public function testHandleCategoriesUnknownSubRoute(): void
    {
        ob_start();
        $this->router->handle('GET', '/api/categories/1/unknown');
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Unknown resource"', $output);
    }
}
