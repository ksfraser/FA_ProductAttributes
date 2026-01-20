<?php

namespace Ksfraser\FA_ProductAttributes\Api;

/**
 * REST API controller for attribute values
 */
class ValuesApiController extends BaseApiController
{
    /**
     * GET /api/categories/{categoryId}/values - List values for category
     * @param int $categoryId
     */
    public function index(int $categoryId): void
    {
        $values = $this->dao->listValues($categoryId);
        $this->jsonResponse(['values' => $values]);
    }

    /**
     * GET /api/categories/{categoryId}/values/{id} - Get single value
     * @param int $categoryId
     * @param int $id
     */
    public function show(int $categoryId, int $id): void
    {
        $values = $this->dao->listValues($categoryId);
        $value = null;

        foreach ($values as $val) {
            if ($val['id'] == $id) {
                $value = $val;
                break;
            }
        }

        if (!$value) {
            $this->errorResponse('Value not found', 404);
        }

        $this->jsonResponse(['value' => $value]);
    }

    /**
     * POST /api/categories/{categoryId}/values - Create new value
     * @param int $categoryId
     */
    public function create(int $categoryId): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['value', 'slug'])) {
            $this->errorResponse('Missing required fields: value, slug');
        }

        try {
            $this->dao->upsertValue(
                $categoryId,
                $data['value'],
                $data['slug'],
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            // Get the created value
            $values = $this->dao->listValues($categoryId);
            $created = null;
            foreach ($values as $val) {
                if ($val['slug'] === $data['slug']) {
                    $created = $val;
                    break;
                }
            }

            $this->jsonResponse(['value' => $created], 201);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to create value: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/categories/{categoryId}/values/{id} - Update value
     * @param int $categoryId
     * @param int $id
     */
    public function update(int $categoryId, int $id): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['value', 'slug'])) {
            $this->errorResponse('Missing required fields: value, slug');
        }

        try {
            $this->dao->upsertValue(
                $categoryId,
                $data['value'],
                $data['slug'],
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            // Get the updated value
            $values = $this->dao->listValues($categoryId);
            $updated = null;
            foreach ($values as $val) {
                if ($val['slug'] === $data['slug']) {
                    $updated = $val;
                    break;
                }
            }

            $this->jsonResponse(['value' => $updated]);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to update value: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/categories/{categoryId}/values/{id} - Delete value
     * @param int $categoryId
     * @param int $id
     */
    public function delete(int $categoryId, int $id): void
    {
        // Check if value exists
        $values = $this->dao->listValues($categoryId);
        $value = null;

        foreach ($values as $val) {
            if ($val['id'] == $id) {
                $value = $val;
                break;
            }
        }

        if (!$value) {
            $this->errorResponse('Value not found', 404);
        }

        // Check if value is in use
        $p = $this->db->getTablePrefix();
        $usage = $this->db->selectAll(
            "SELECT COUNT(*) as count FROM {$p}product_attribute_assignments WHERE value_id = :value_id",
            ['value_id' => $id]
        );

        if ($usage[0]['count'] > 0) {
            $this->errorResponse('Cannot delete value that is in use by products', 409);
        }

        try {
            // Deactivate instead of delete
            $this->dao->upsertValue(
                $categoryId,
                $value['value'],
                $value['slug'],
                $value['sort_order'],
                false // Deactivate
            );

            $this->jsonResponse(['message' => 'Value deactivated']);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to deactivate value: ' . $e->getMessage(), 500);
        }
    }
}