<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Service\BulkOperationsService;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class BulkOperationsServiceTest extends TestCase
{
    private $dao;
    private $db;
    private $service;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->service = new BulkOperationsService($this->dao, $this->db);
    }

    public function testBulkAssignAttributes(): void
    {
        $stockIds = ['PROD1', 'PROD2', 'PROD3'];
        $attributeAssignments = [
            ['category_id' => 1, 'value_id' => 10],
            ['category_id' => 2, 'value_id' => 20]
        ];

        $this->dao->expects($this->exactly(6)) // 3 products * 2 assignments
            ->method('addAssignment')
            ->withConsecutive(
                ['PROD1', 1, 10],
                ['PROD1', 2, 20],
                ['PROD2', 1, 10],
                ['PROD2', 2, 20],
                ['PROD3', 1, 10],
                ['PROD3', 2, 20]
            );

        $result = $this->service->bulkAssignAttributes($stockIds, $attributeAssignments);

        $this->assertTrue($result['success']);
        $this->assertEquals(6, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testBulkAssignAttributesWithFailures(): void
    {
        $stockIds = ['PROD1', 'PROD2'];
        $attributeAssignments = [
            ['category_id' => 1, 'value_id' => 10]
        ];

        $this->dao->expects($this->exactly(2))
            ->method('addAssignment')
            ->willReturnCallback(function($stockId, $categoryId, $valueId) {
                if ($stockId === 'PROD2') {
                    throw new \Exception('Assignment failed');
                }
                return true;
            });

        $result = $this->service->bulkAssignAttributes($stockIds, $attributeAssignments);

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    public function testBulkDeleteAttributes(): void
    {
        $stockIds = ['PROD1', 'PROD2'];
        $categoryIds = [1, 2];

        $this->dao->expects($this->exactly(4)) // 2 products * 2 categories
            ->method('removeCategoryAssignment')
            ->withConsecutive(
                ['PROD1', 1],
                ['PROD1', 2],
                ['PROD2', 1],
                ['PROD2', 2]
            );

        $result = $this->service->bulkDeleteAttributes($stockIds, $categoryIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testBulkDeleteAttributesWithFailures(): void
    {
        $stockIds = ['PROD1', 'PROD2'];
        $categoryIds = [1];

        $this->dao->expects($this->exactly(2))
            ->method('removeCategoryAssignment')
            ->willReturnCallback(function($stockId, $categoryId) {
                if ($stockId === 'PROD2') {
                    throw new \Exception('Delete failed');
                }
                return true;
            });

        $result = $this->service->bulkDeleteAttributes($stockIds, $categoryIds);

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    public function testRegisterOperation(): void
    {
        $operationName = 'test_operation';
        $operationFunction = function($products, $params) {
            return ['result' => 'success'];
        };

        $this->service->registerOperation($operationName, $operationFunction);

        // Test that the operation is registered by executing it
        $result = $this->service->executeCustomOperation($operationName, [1, 2], []);
        $this->assertEquals(['result' => 'success'], $result);
    }

    public function testExecuteCustomOperation(): void
    {
        $operation = [
            'name' => 'custom_sync',
            'products' => [1, 2, 3],
            'params' => ['sync_target' => 'external_api']
        ];

        $this->service->registerOperation('custom_sync', function($products, $params) {
            return [
                'success' => true,
                'processed' => count($products),
                'message' => 'Synced to ' . $params['sync_target']
            ];
        });

        $result = $this->service->executeCustomOperation('custom_sync', $operation['products'], $operation['params']);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals('Synced to external_api', $result['message']);
    }

    public function testExecuteUnknownCustomOperation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->executeCustomOperation('unknown_operation', [1, 2], []);
    }

    public function testValidateBulkOperation(): void
    {
        $operation = [
            'type' => 'assign_attributes',
            'product_ids' => [1, 2, 3],
            'attributes' => [
                ['category_id' => 1, 'value_id' => 10]
            ]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertTrue($isValid);
    }

    public function testValidateBulkOperationDelete(): void
    {
        $operation = [
            'type' => 'delete_attributes',
            'product_ids' => [1, 2],
            'category_ids' => [1, 2]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertTrue($isValid);
    }

    public function testValidateBulkOperationMissingType(): void
    {
        $operation = [
            'product_ids' => [1, 2, 3]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationMissingProductIds(): void
    {
        $operation = [
            'type' => 'assign_attributes'
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationEmptyProductIds(): void
    {
        $operation = [
            'type' => 'assign_attributes',
            'product_ids' => []
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationInvalidType(): void
    {
        $operation = [
            'type' => 'invalid_type',
            'product_ids' => [1, 2, 3]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationAssignMissingAttributes(): void
    {
        $operation = [
            'type' => 'assign_attributes',
            'product_ids' => [1, 2, 3]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationAssignEmptyAttributes(): void
    {
        $operation = [
            'type' => 'assign_attributes',
            'product_ids' => [1, 2, 3],
            'attributes' => []
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationDeleteMissingCategoryIds(): void
    {
        $operation = [
            'type' => 'delete_attributes',
            'product_ids' => [1, 2]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationDeleteEmptyCategoryIds(): void
    {
        $operation = [
            'type' => 'delete_attributes',
            'product_ids' => [1, 2],
            'category_ids' => []
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertFalse($isValid);
    }

    public function testValidateBulkOperationCustom(): void
    {
        $this->service->registerOperation('custom_op', function($products, $params) {
            return ['success' => true];
        });

        $operation = [
            'type' => 'custom_op',
            'product_ids' => [1, 2, 3]
        ];

        $isValid = $this->service->validateBulkOperation($operation);
        $this->assertTrue($isValid);
    }
}