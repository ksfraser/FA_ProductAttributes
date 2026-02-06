<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\AssignmentsApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class AssignmentsApiControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([
                ['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
                ['id' => 2, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 2, 'sort_order' => 1]
            ]);

        $controller = new AssignmentsApiController($dao, $db, true);

        ob_start();
        $controller->index('ABC123');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('assignments', $response);
        $this->assertCount(2, $response['assignments']);
    }

    public function testShow(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([
                ['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
                ['id' => 2, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 2, 'sort_order' => 1]
            ]);

        $controller = new AssignmentsApiController($dao, $db, true);

        ob_start();
        $controller->show('ABC123', 1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('assignment', $response);
        $this->assertEquals(1, $response['assignment']['id']);
    }

    public function testShowNotFound(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1]]);

        $controller = new AssignmentsApiController($dao, $db, true);

        ob_start();
        $controller->show('ABC123', 999);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals(['error' => 'Assignment not found'], $response);
    }

    public function testCreateSuccess(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red']]);

        $dao->expects($this->once())
            ->method('addAssignment')
            ->with('ABC123', 1, 1, 0);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'sort_order' => 0]]);

        // Create a test controller that overrides getJsonInput
        $controller = new class($dao, $db, true) extends AssignmentsApiController {
            private $testInput = ['category_id' => 1, 'value_id' => 1];
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        ob_start();
        $controller->create('ABC123');
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Output should not be empty');
        $response = json_decode($output, true);
        $this->assertNotNull($response, 'JSON decode should not return null. Output: ' . $output);
        $this->assertArrayHasKey('assignment', $response);
        $this->assertEquals(1, $response['assignment']['category_id']);
    }

    public function testCreateInvalidCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $controller = new class($dao, $db, true) extends AssignmentsApiController {
            private $testInput = ['category_id' => 999, 'value_id' => 1]; // Invalid category
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        ob_start();
        $controller->create('ABC123');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals(['error' => 'Invalid category_id'], $response);
    }

    public function testCreateInvalidValue(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([['id' => 1, 'code' => 'color', 'label' => 'Color']]);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red']]);

        $controller = new class($dao, $db, true) extends AssignmentsApiController {
            private $testInput = ['category_id' => 1, 'value_id' => 999]; // Invalid value
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        ob_start();
        $controller->create('ABC123');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals(['error' => 'Invalid value_id for the specified category'], $response);
    }

    public function testDeleteSuccess(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1]]);

        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(1);

        $controller = new AssignmentsApiController($dao, $db, true);

        ob_start();
        $controller->delete('ABC123', 1);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Assignment deleted', $response['message']);
    }

    public function testDeleteNotFound(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1]]);

        $controller = new AssignmentsApiController($dao, $db, true);

        ob_start();
        $controller->delete('ABC123', 999);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertEquals(['error' => 'Assignment not found'], $response);
    }

    public function testBulkUpdate(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM fa_product_attribute_assignments WHERE stock_id = :stock_id', ['stock_id' => 'ABC123']);

        $dao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['ABC123', 1, 1, 0],
                ['ABC123', 1, 2, 1]
            );

        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([
                ['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
                ['id' => 2, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 2, 'sort_order' => 1]
            ]);

        $controller = new class($dao, $db, true) extends AssignmentsApiController {
            private $testInput = [
                'assignments' => [
                    ['category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
                    ['category_id' => 1, 'value_id' => 2, 'sort_order' => 1]
                ]
            ];
            
            protected function getJsonInput(): array {
                return $this->testInput;
            }
        };

        ob_start();
        $controller->bulkUpdate('ABC123');
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertArrayHasKey('assignments', $response);
        $this->assertCount(2, $response['assignments']);
    }
}