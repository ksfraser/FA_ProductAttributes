<?php

namespace Ksfraser\FA_ProductAttributes\Api;

/**
 * REST API controller for product attribute assignments.
 *
 * GET    /api/products/{stockId}/assignments
 * POST   /api/products/{stockId}/assignments
 * PUT    /api/products/{stockId}/assignments     (bulk update)
 * GET    /api/products/{stockId}/assignments/{id}
 * DELETE /api/products/{stockId}/assignments/{id}
 */
class AssignmentsApiController extends BaseApiController
{
    /**
     * GET /api/products/{stockId}/assignments — list all assignments for a product.
     */
    public function index(string $stockId): void
    {
        $assignments = $this->dao->listAssignments($stockId);
        $this->jsonResponse(['assignments' => $assignments]);
    }

    /**
     * GET /api/products/{stockId}/assignments/{id} — get one assignment.
     */
    public function show(string $stockId, int $id): void
    {
        $assignments = $this->dao->listAssignments($stockId);
        $assignment  = null;

        foreach ($assignments as $a) {
            if ($a['id'] == $id) {
                $assignment = $a;
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
     * POST /api/products/{stockId}/assignments — create an assignment.
     */
    public function create(string $stockId): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['category_id', 'value_id'])) {
            $this->errorResponse('Missing required fields: category_id, value_id');
            return;
        }

        $categoryId = (int) $data['category_id'];
        $valueId    = (int) $data['value_id'];
        $sortOrder  = (int) ($data['sort_order'] ?? 0);

        // Validate category exists
        $categories = $this->dao->listCategories();
        $catFound   = false;
        foreach ($categories as $cat) {
            if ($cat['id'] == $categoryId) {
                $catFound = true;
                break;
            }
        }

        if (!$catFound) {
            $this->errorResponse('Invalid category_id', 400);
            return;
        }

        // Validate value belongs to the category
        $values     = $this->dao->listValues($categoryId);
        $valFound   = false;
        foreach ($values as $val) {
            if ($val['id'] == $valueId) {
                $valFound = true;
                break;
            }
        }

        if (!$valFound) {
            $this->errorResponse('Invalid value_id for the specified category', 400);
            return;
        }

        try {
            $this->dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);

            $assignments = $this->dao->listAssignments($stockId);
            $created     = end($assignments);

            $this->jsonResponse(['assignment' => $created], 201);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to create assignment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/products/{stockId}/assignments/{id} — delete one assignment.
     */
    public function delete(string $stockId, int $id): void
    {
        $assignments = $this->dao->listAssignments($stockId);
        $found       = false;

        foreach ($assignments as $a) {
            if ($a['id'] == $id) {
                $found = true;
                break;
            }
        }

        if (!$found) {
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
     * PUT /api/products/{stockId}/assignments — bulk replace all assignments for a product.
     *   Body: {"assignments": [{"category_id":1,"value_id":2,"sort_order":0}, ...]}
     */
    public function bulkUpdate(string $stockId): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['assignments'])) {
            $this->errorResponse('Missing required field: assignments');
            return;
        }

        if (!is_array($data['assignments'])) {
            $this->errorResponse('assignments must be an array');
            return;
        }

        try {
            $p = $this->db->getTablePrefix();
            $this->db->execute(
                "DELETE FROM {$p}product_attribute_assignments WHERE stock_id = :stock_id",
                ['stock_id' => $stockId]
            );

            foreach ($data['assignments'] as $item) {
                $this->dao->addAssignment(
                    $stockId,
                    (int) ($item['category_id'] ?? 0),
                    (int) ($item['value_id'] ?? 0),
                    (int) ($item['sort_order'] ?? 0)
                );
            }

            $assignments = $this->dao->listAssignments($stockId);
            $this->jsonResponse(['assignments' => $assignments]);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to bulk update assignments: ' . $e->getMessage(), 500);
        }
    }
}
