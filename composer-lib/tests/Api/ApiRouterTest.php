<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\ApiRouter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ApiRouterTest extends TestCase
{
    public function testHandleCategoriesIndex(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $router = new ApiRouter($dao, $db, true);

        ob_start();
        $router->handle('GET', 'categories');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('categories', $response);
    }

    public function testHandleCategoriesShow(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $router = new ApiRouter($dao, $db, true);

        ob_start();
        $router->handle('GET', 'categories/1');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('color', $response['category']['code']);
    }

    public function testHandleValuesIndex(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'value' => 'Red', 'slug' => 'red']]);

        $router = new ApiRouter($dao, $db, true);

        ob_start();
        $router->handle('GET', 'categories/1/values');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('values', $response);
    }

    public function testHandleAssignmentsIndex(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([['id' => 1, 'stock_id' => 'ABC123', 'category_code' => 'color']]);

        $router = new ApiRouter($dao, $db, true);

        ob_start();
        $router->handle('GET', 'products/ABC123/assignments');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('assignments', $response);
    }

    public function testHandleUnknownResource(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $router = new ApiRouter($dao, $db, true);

        ob_start();
        $router->handle('GET', 'unknown');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertNotNull($response, 'JSON decode failed for output: ' . $output);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Unknown resource', $response['error']);
    }

    public function testHandleMethodNotAllowed(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $router = new ApiRouter($dao, $db, true);

        ob_start();
        $router->handle('PATCH', 'categories');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Method not allowed', $response['error']);
    }
}