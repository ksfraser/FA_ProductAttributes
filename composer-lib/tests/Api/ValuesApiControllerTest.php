<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\ValuesApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ValuesApiControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([
                ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0, 'active' => true],
                ['id' => 2, 'category_id' => 1, 'value' => 'Blue', 'slug' => 'blue', 'sort_order' => 1, 'active' => true]
            ]);

        $controller = new ValuesApiController($dao, $db, true);

        ob_start();
        $controller->index(1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('values', $response);
        $this->assertCount(2, $response['values']);
        $this->assertEquals('Red', $response['values'][0]['value']);
    }

    public function testShow(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([
                ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0, 'active' => true],
                ['id' => 2, 'category_id' => 1, 'value' => 'Blue', 'slug' => 'blue', 'sort_order' => 1, 'active' => true]
            ]);

        $controller = new ValuesApiController($dao, $db, true);

        ob_start();
        $controller->show(1, 1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('value', $response);
        $this->assertEquals('Red', $response['value']['value']);
    }

    public function testShowNotFound(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red']]);

        $controller = new ValuesApiController($dao, $db, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error: Value not found');
        
        $controller->show(1, 999);
    }

    public function testCreateSuccess(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0, 'active' => true]]);

        $dao->expects($this->once())
            ->method('upsertValue')
            ->with(1, 'Red', 'red', 0, true);

        // Create a test controller that overrides getJsonInput
        $controller = new class($dao, $db, true) extends ValuesApiController {
            private $testInput = ['value' => 'Red', 'slug' => 'red'];
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        ob_start();
        $controller->create(1);
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Output should not be empty');
        $response = json_decode($output, true);
        $this->assertNotNull($response, 'JSON decode should not return null. Output: ' . $output);
        $this->assertArrayHasKey('value', $response);
        $this->assertEquals('Red', $response['value']['value']);
    }

    public function testCreateMissingFields(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new ValuesApiController($dao, $db, true);

        // Create a test controller with invalid input
        $controller = new class($dao, $db, true) extends ValuesApiController {
            private $testInput = ['value' => 'Red']; // Missing slug
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error: Missing required fields: value, slug');
        
        $controller->create(1);
    }

    public function testDeleteSuccess(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0, 'active' => true]]);

        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT COUNT(*) as count FROM fa_product_attribute_assignments WHERE value_id = :value_id', ['value_id' => 1])
            ->willReturn([['count' => 0]]);

        $dao->expects($this->once())
            ->method('upsertValue')
            ->with(1, 'Red', 'red', 0, false);

        $controller = new ValuesApiController($dao, $db, true);

        ob_start();
        $controller->delete(1, 1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Value deactivated', $response['message']);
    }

    public function testDeleteNotFound(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red']]);

        $controller = new ValuesApiController($dao, $db, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error: Value not found');
        
        $controller->delete(1, 999);
    }

    public function testDeleteInUse(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 0, 'active' => true]]);

        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT COUNT(*) as count FROM fa_product_attribute_assignments WHERE value_id = :value_id', ['value_id' => 1])
            ->willReturn([['count' => 1]]);

        $controller = new ValuesApiController($dao, $db, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error: Cannot delete value that is in use by products');
        
        $controller->delete(1, 1);
    }
}