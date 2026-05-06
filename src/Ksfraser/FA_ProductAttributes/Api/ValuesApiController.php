<?php

namespace Ksfraser\FA_ProductAttributes\Api;

/**
 * REST API controller for attribute values within a category.
 *
 * GET    /api/categories/{categoryId}/values
 * POST   /api/categories/{categoryId}/values
 * GET    /api/categories/{categoryId}/values/{id}
 * PUT    /api/categories/{categoryId}/values/{id}
 * DELETE /api/categories/{categoryId}/values/{id}
 */
class ValuesApiController extends BaseApiController
{
    /**
     * GET /api/categories/{categoryId}/values — list values for a category.
     */
    public function index(int $categoryId): void
    {
        $values = $this->dao->listValues($categoryId);
        $this->jsonResponse(['values' => $values]);
    }

    /**
     * GET /api/categories/{categoryId}/values/{id} — get one value.
     */
    public function show(int $categoryId, int $id): void
    {
        $values = $this->dao->listValues($categoryId);
        $value  = null;

        foreach ($values as $val) {
            if ($val['id'] == $id) {
                $value = $val;
                break;
            }
        }

        if (!$value) {
            $this->errorResponse('Value not found', 404);
            return;
        }

        $this->jsonResponse(['value' => $value]);
    }

    /**
     * POST /api/categories/{categoryId}/values — create a value.
     */
    public function create(int $categoryId): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['value', 'slug'])) {
            $this->errorResponse('Missing required fields: value, slug');
            return;
        }

        try {
            $this->dao->upsertValue(
                $categoryId,
                $data['value'],
                $data['slug'],
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            $values  = $this->dao->listValues($categoryId);
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
     * PUT /api/categories/{categoryId}/values/{id} — update a value.
     */
    public function update(int $categoryId, int $id): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['value', 'slug'])) {
            $this->errorResponse('Missing required fields: value, slug');
            return;
        }

        try {
            $this->dao->upsertValue(
                $categoryId,
                $data['value'],
                $data['slug'],
                $data['sort_order'] ?? 0,
                $data['active'] ?? true,
                $id
            );

            $values  = $this->dao->listValues($categoryId);
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
     * DELETE /api/categories/{categoryId}/values/{id} — deactivate (soft-delete) a value.
     *   Returns 409 if the value is in use by products.
     */
    public function delete(int $categoryId, int $id): void
    {
        $values = $this->dao->listValues($categoryId);
        $value  = null;

        foreach ($values as $val) {
            if ($val['id'] == $id) {
                $value = $val;
                break;
            }
        }

        if (!$value) {
            $this->errorResponse('Value not found', 404);
            return;
        }

        $p     = $this->db->getTablePrefix();
        $usage = $this->db->query(
            "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE value_id = :value_id",
            ['value_id' => $id]
        );

        if ($usage[0]['count'] > 0) {
            $this->errorResponse('Cannot delete value that is in use by products', 409);
            return;
        }

        try {
            $this->dao->upsertValue(
                $categoryId,
                $value['value'],
                $value['slug'],
                $value['sort_order'] ?? 0,
                false,
                $id
            );

            $this->jsonResponse(['message' => 'Value deactivated']);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to deactivate value: ' . $e->getMessage(), 500);
        }
    }
}
