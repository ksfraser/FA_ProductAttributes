<?php

namespace Ksfraser\FA_ProductAttributes\Api;

/**
 * REST API controller for product attribute assignments
 */
class AssignmentsApiController extends BaseApiController
{
    /**
     * GET /api/products/{stockId}/assignments - List assignments for product
     * @param string $stockId
     */
    public function index(string $stockId): void
    {
        $assignments = $this->dao->listAssignments($stockId);
        $this->jsonResponse(['assignments' => $assignments]);
    }

    /**
     * GET /api/products/{stockId}/assignments/{id} - Get single assignment
     * @param string $stockId
     * @param int $id
     */
    public function show(string $stockId, int $id): void
    {
        $assignments = $this->dao->listAssignments($stockId);
        $assignment = null;

        foreach ($assignments as $assign) {
            if ($assign['id'] == $id) {
                $assignment = $assign;
                break;
            }
        }

        if (!$assignment) {
            $this->errorResponse('Assignment not found', 404);
            return;
        }

        $this->jsonResponse(['assignment' => $assignment]);
    }

    /**
     * POST /api/products/{stockId}/assignments - Create new assignment
     * @param string $stockId
     */
    public function create(string $stockId): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['category_id', 'value_id'])) {
            $this->errorResponse('Missing required fields: category_id, value_id');
            return;
        }

        // Validate that category and value exist
        $categories = $this->dao->listCategories();
        $categoryExists = false;
        foreach ($categories as $cat) {
            if ($cat['id'] == $data['category_id']) {
                $categoryExists = true;
                break;
            }
        }

        if (!$categoryExists) {
            $this->errorResponse('Invalid category_id', 400);
            return;
        }

        $values = $this->dao->listValues($data['category_id']);
        $valueExists = false;
        foreach ($values as $val) {
            if ($val['id'] == $data['value_id']) {
                $valueExists = true;
                break;
            }
        }

        if (!$valueExists) {
            $this->errorResponse('Invalid value_id for the specified category', 400);
            return;
        }

        try {
            $this->dao->addAssignment(
                $stockId,
                $data['category_id'],
                $data['value_id'],
                $data['sort_order'] ?? 0
            );

            // Get the created assignment
            $assignments = $this->dao->listAssignments($stockId);
            $created = end($assignments); // Last assignment should be the one we just created

            $this->jsonResponse(['assignment' => $created], 201);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to create assignment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/products/{stockId}/assignments/{id} - Delete assignment
     * @param string $stockId
     * @param int $id
     */
    public function delete(string $stockId, int $id): void
    {
        // Check if assignment exists
        $assignments = $this->dao->listAssignments($stockId);
        $assignment = null;

        foreach ($assignments as $assign) {
            if ($assign['id'] == $id) {
                $assignment = $assign;
                break;
            }
        }

        if (!$assignment) {
            $this->errorResponse('Assignment not found', 404);
            return;
        }

        try {
            $this->dao->deleteAssignment($id);
            $this->jsonResponse(['message' => 'Assignment deleted']);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to delete assignment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/products/{stockId}/assignments - Bulk update assignments
     * @param string $stockId
     */
    public function bulkUpdate(string $stockId): void
    {
        $data = $this->getJsonInput();

        if (!isset($data['assignments']) || !is_array($data['assignments'])) {
            $this->errorResponse('Missing or invalid assignments array');
        }

        try {
            // Delete existing assignments
            $p = $this->db->getTablePrefix();
            $this->db->execute(
                "DELETE FROM {$p}product_attribute_assignments WHERE stock_id = :stock_id",
                ['stock_id' => $stockId]
            );

            // Add new assignments
            foreach ($data['assignments'] as $assignment) {
                if (isset($assignment['category_id']) && isset($assignment['value_id'])) {
                    $this->dao->addAssignment(
                        $stockId,
                        $assignment['category_id'],
                        $assignment['value_id'],
                        $assignment['sort_order'] ?? 0
                    );
                }
            }

            $assignments = $this->dao->listAssignments($stockId);
            $this->jsonResponse(['assignments' => $assignments]);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to update assignments: ' . $e->getMessage(), 500);
        }
    }
}