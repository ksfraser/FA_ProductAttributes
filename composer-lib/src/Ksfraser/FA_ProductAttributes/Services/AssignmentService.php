<?php

namespace Ksfraser\FA_ProductAttributes\Services;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * Service class for assignment business logic
 *
 * Handles validation, business rules, and data operations for product attribute assignments.
 * Follows Single Responsibility Principle by encapsulating all assignment-related business logic.
 *
 * @package Ksfraser\FA_ProductAttributes\Services
 */
class AssignmentService
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    /**
     * Constructor with dependency injection
     *
     * @param ProductAttributesDao $dao Data access object
     * @param DbAdapterInterface $dbAdapter Database adapter for queries
     */
    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * Create a new assignment
     *
     * @param array $data Assignment data (stock_id, category_id, value_id, sort_order)
     * @return array Created assignment data
     * @throws \InvalidArgumentException When validation fails
     */
    public function createAssignment(array $data): array
    {
        $this->validateAssignmentData($data);

        $stockId = trim($data['stock_id']);
        $categoryId = (int)$data['category_id'];
        $valueId = (int)$data['value_id'];
        $sortOrder = (int)($data['sort_order'] ?? 0);

        $this->dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);

        // Return the created assignment
        $assignments = $this->dao->listAssignments($stockId);
        foreach ($assignments as $assignment) {
            if ((int)$assignment['category_id'] === $categoryId && (int)$assignment['value_id'] === $valueId) {
                return $assignment;
            }
        }

        throw new \RuntimeException('Failed to retrieve created assignment');
    }

    /**
     * Delete an assignment
     *
     * @param int $assignmentId Assignment ID to delete
     * @return array Delete result with status and message
     * @throws \InvalidArgumentException When assignment ID is invalid
     */
    public function deleteAssignment(int $assignmentId): array
    {
        if ($assignmentId <= 0) {
            throw new \InvalidArgumentException('Assignment ID must be greater than 0');
        }

        $this->dao->deleteAssignment($assignmentId);

        return [
            'deleted' => true,
            'message' => 'Assignment deleted successfully'
        ];
    }

    /**
     * Get all assignments for a stock item
     *
     * @param string $stockId Stock ID
     * @return array List of assignments for the stock item
     */
    public function getAssignmentsByStockId(string $stockId): array
    {
        return $this->dao->listAssignments($stockId);
    }

    /**
     * Validate assignment data
     *
     * @param array $data Assignment data to validate
     * @throws \InvalidArgumentException When validation fails
     */
    private function validateAssignmentData(array $data): void
    {
        $stockId = trim($data['stock_id'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 0);
        $valueId = (int)($data['value_id'] ?? 0);

        if (empty($stockId)) {
            throw new \InvalidArgumentException('Stock ID, category ID, and value ID are required');
        }

        if ($categoryId <= 0) {
            throw new \InvalidArgumentException('Stock ID, category ID, and value ID are required');
        }

        if ($valueId <= 0) {
            throw new \InvalidArgumentException('Stock ID, category ID, and value ID are required');
        }
    }
}