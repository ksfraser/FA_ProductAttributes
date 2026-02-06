<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class BulkOperationsService
{
    private $dao;
    private $db;
    private $customOperations = [];

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db)
    {
        $this->dao = $dao;
        $this->db = $db;
    }

    /**
     * Register a custom bulk operation
     * @param string $operationName
     * @param callable $operationFunction
     */
    public function registerOperation(string $operationName, callable $operationFunction): void
    {
        $this->customOperations[$operationName] = $operationFunction;
    }

    /**
     * Bulk assign attributes to multiple products
     * @param array<string> $stockIds
     * @param array<array{category_id: int, value_id: int}> $attributeAssignments
     * @return array{success: bool, processed: int, failed: int, errors: array}
     */
    public function bulkAssignAttributes(array $stockIds, array $attributeAssignments): array
    {
        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($stockIds as $stockId) {
            foreach ($attributeAssignments as $assignment) {
                try {
                    $this->dao->addAssignment(
                        $stockId,
                        $assignment['category_id'],
                        $assignment['value_id']
                    );
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'stock_id' => $stockId,
                        'category_id' => $assignment['category_id'],
                        'value_id' => $assignment['value_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'success' => $failed === 0,
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Bulk delete attributes from multiple products
     * @param array<string> $stockIds
     * @param array<int> $categoryIds
     * @return array{success: bool, processed: int, failed: int, errors: array}
     */
    public function bulkDeleteAttributes(array $stockIds, array $categoryIds): array
    {
        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($stockIds as $stockId) {
            foreach ($categoryIds as $categoryId) {
                try {
                    $this->dao->removeCategoryAssignment($stockId, $categoryId);
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'stock_id' => $stockId,
                        'category_id' => $categoryId,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        return [
            'success' => $failed === 0,
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Execute a custom registered operation
     * @param string $operationName
     * @param array $products
     * @param array $params
     * @return array
     */
    public function executeCustomOperation(string $operationName, array $products, array $params = []): array
    {
        if (!isset($this->customOperations[$operationName])) {
            throw new \InvalidArgumentException("Unknown custom operation: {$operationName}");
        }

        return call_user_func($this->customOperations[$operationName], $products, $params);
    }

    /**
     * Validate a bulk operation configuration
     * @param array $operation
     * @return bool
     */
    public function validateBulkOperation(array $operation): bool
    {
        if (!isset($operation['type']) || !isset($operation['product_ids'])) {
            return false;
        }

        $validTypes = [
            'assign_attributes',
            'delete_attributes'
        ];

        if (!in_array($operation['type'], $validTypes) && !isset($this->customOperations[$operation['type']])) {
            return false;
        }

        if (!is_array($operation['product_ids']) || empty($operation['product_ids'])) {
            return false;
        }

        // Type-specific validation
        switch ($operation['type']) {
            case 'assign_attributes':
                return isset($operation['attributes']) &&
                       is_array($operation['attributes']) &&
                       !empty($operation['attributes']);

            case 'delete_attributes':
                return isset($operation['category_ids']) &&
                       is_array($operation['category_ids']) &&
                       !empty($operation['category_ids']);

            default:
                // Custom operations don't need additional validation
                return true;
        }
    }
}