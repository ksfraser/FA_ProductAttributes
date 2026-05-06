<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Provides framework for bulk attribute operations.
 *
 * Core module defines and executes built-in bulk operations.
 * Plugins may register custom operations via registerOperation().
 */
class BulkOperationsService
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $db;

    /** @var array<string, callable> Custom operations registered by plugins */
    private $customOperations = [];

    /** @var array<string, bool> Known built-in operation types */
    private static $builtinTypes = [
        'assign_attributes'  => true,
        'delete_attributes'  => true,
        'status_change'      => true,
        'category_assignment' => true,
    ];

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db)
    {
        $this->dao = $dao;
        $this->db  = $db;
    }

    /**
     * Register a custom bulk operation.
     *
     * @param string   $name     Unique operation name
     * @param callable $callback function(array $productIds, array $params): array
     */
    public function registerOperation(string $name, callable $callback): void
    {
        $this->customOperations[$name] = $callback;
    }

    /**
     * Execute a registered custom operation.
     *
     * @param string $name
     * @param array<int, mixed> $productIds
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws \InvalidArgumentException if operation not registered
     */
    public function executeCustomOperation(string $name, array $productIds, array $params): array
    {
        if (!isset($this->customOperations[$name])) {
            throw new \InvalidArgumentException("Unknown bulk operation: {$name}");
        }

        return ($this->customOperations[$name])($productIds, $params);
    }

    /**
     * Validate a bulk-operation descriptor.
     *
     * @param array<string, mixed> $operation
     * @return bool
     */
    public function validateBulkOperation(array $operation): bool
    {
        $type       = $operation['type'] ?? null;
        $productIds = $operation['product_ids'] ?? null;

        if ($type === null || !is_array($productIds) || empty($productIds)) {
            return false;
        }

        // Builtin types need additional field checks
        if (isset(self::$builtinTypes[$type])) {
            if ($type === 'assign_attributes') {
                $attrs = $operation['attributes'] ?? null;
                return is_array($attrs) && !empty($attrs);
            }
            if ($type === 'delete_attributes') {
                $cids = $operation['category_ids'] ?? null;
                return is_array($cids) && !empty($cids);
            }
            return true;
        }

        // Custom operations: just needs type + product_ids (already validated)
        return isset($this->customOperations[$type]);
    }

    /**
     * Bulk assign attribute assignments across multiple products.
     *
     * @param array<int, string> $stockIds
     * @param array<int, array<string, int>> $attributeAssignments  Each: ['category_id'=>x, 'value_id'=>y]
     * @return array<string, mixed> ['success'=>bool, 'processed'=>int, 'failed'=>int, 'errors'=>array]
     */
    public function bulkAssignAttributes(array $stockIds, array $attributeAssignments): array
    {
        $processed = 0;
        $failed    = 0;
        $errors    = [];

        foreach ($stockIds as $stockId) {
            foreach ($attributeAssignments as $assignment) {
                try {
                    $this->dao->addAssignment(
                        $stockId,
                        (int)$assignment['category_id'],
                        (int)$assignment['value_id']
                    );
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Failed for {$stockId}: " . $e->getMessage();
                }
            }
        }

        return [
            'success'   => $failed === 0,
            'processed' => $processed,
            'failed'    => $failed,
            'errors'    => $errors,
        ];
    }

    /**
     * Bulk remove category assignments across multiple products.
     *
     * @param array<int, string> $stockIds
     * @param array<int, int>    $categoryIds
     * @return array<string, mixed>
     */
    public function bulkDeleteAttributes(array $stockIds, array $categoryIds): array
    {
        $processed = 0;
        $failed    = 0;
        $errors    = [];

        foreach ($stockIds as $stockId) {
            foreach ($categoryIds as $categoryId) {
                try {
                    $this->dao->removeCategoryAssignment($stockId, (int)$categoryId);
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Failed for {$stockId}: " . $e->getMessage();
                }
            }
        }

        return [
            'success'   => $failed === 0,
            'processed' => $processed,
            'failed'    => $failed,
            'errors'    => $errors,
        ];
    }
}
