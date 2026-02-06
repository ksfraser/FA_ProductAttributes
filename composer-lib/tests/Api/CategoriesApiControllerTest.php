<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\CategoriesApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class CategoriesApiControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'color', 'label' => 'Color'],
                ['id' => 2, 'code' => 'size', 'label' => 'Size']
            ]);

        $controller = new CategoriesApiController($dao, $db, true);

        // Capture output
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('categories', $response);
        $this->assertCount(2, $response['categories']);
    }

    public function testShowFound(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'color', 'label' => 'Color'],
                ['id' => 2, 'code' => 'size', 'label' => 'Size']
            ]);

        $controller = new CategoriesApiController($dao, $db, true);

        ob_start();
        $controller->show(1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('color', $response['category']['code']);
    }

    public function testShowNotFound(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $controller = new CategoriesApiController($dao, $db, true);

        ob_start();
        $controller->show(999);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals(['error' => 'Category not found'], $response);
    }

    public function testCreateSuccess(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $dao->expects($this->once())
            ->method('upsertCategory')
            ->with('color', 'Color', '', 0, true);

        // Create a test controller that overrides getJsonInput
        $controller = new class($dao, $db, true) extends CategoriesApiController {
            private $testInput = ['code' => 'color', 'label' => 'Color'];
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        ob_start();
        $controller->create();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Output should not be empty');
        $response = json_decode($output, true);
        $this->assertNotNull($response, 'JSON decode should not return null. Output: ' . $output);
        $this->assertArrayHasKey('category', $response);
        $this->assertEquals('color', $response['category']['code']);
    }

    public function testCreateMissingFields(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new CategoriesApiController($dao, $db);

        // Test the validateRequired method directly
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateRequired');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($controller, [['code' => 'color', 'label' => 'Color'], ['code', 'label']]));
        $this->assertFalse($method->invokeArgs($controller, [['code' => 'color'], ['code', 'label']]));
    }

    public function testDeleteInUse(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE category_id = :category_id', ['category_id' => 1])
            ->willReturn([['count' => 1]]);

        $controller = new CategoriesApiController($dao, $db, true);

        ob_start();
        $controller->delete(1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals(['error' => 'Cannot delete category that is in use by products'], $response);
    }
}