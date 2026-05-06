<?php

namespace Ksfraser\FA_ProductAttributes\Api;

/**
 * REST API controller for attribute categories.
 *
 * GET    /api/categories
 * POST   /api/categories
 * GET    /api/categories/{id}
 * PUT    /api/categories/{id}
 * DELETE /api/categories/{id}
 */
class CategoriesApiController extends BaseApiController
{
    /**
     * GET /api/categories — list all categories.
     */
    public function index(): void
    {
        $categories = $this->dao->listCategories();
        $this->jsonResponse(['categories' => $categories]);
    }

    /**
     * GET /api/categories/{id} — get one category.
     */
    public function show(int $id): void
    {
        $categories = $this->dao->listCategories();
        $category   = null;

        foreach ($categories as $cat) {
            if ($cat['id'] == $id) {
                $category = $cat;
                break;
            }
        }

        if (!$category) {
            $this->errorResponse('Category not found', 404);
            return;
        }

        $this->jsonResponse(['category' => $category]);
    }

    /**
     * POST /api/categories — create a category.
     */
    public function create(): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['code', 'label'])) {
            $this->errorResponse('Missing required fields: code, label');
            return;
        }

        try {
            $this->dao->upsertCategory(
                $data['code'],
                $data['label'],
                $data['description'] ?? '',
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            $categories = $this->dao->listCategories();
            $created    = null;
            foreach ($categories as $cat) {
                if ($cat['code'] === $data['code']) {
                    $created = $cat;
                    break;
                }
            }

            $this->jsonResponse(['category' => $created], 201);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to create category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/categories/{id} — update a category.
     */
    public function update(int $id): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['code', 'label'])) {
            $this->errorResponse('Missing required fields: code, label');
            return;
        }

        try {
            $this->dao->upsertCategory(
                $data['code'],
                $data['label'],
                $data['description'] ?? '',
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            $categories = $this->dao->listCategories();
            $updated    = null;
            foreach ($categories as $cat) {
                if ($cat['code'] === $data['code']) {
                    $updated = $cat;
                    break;
                }
            }

            $this->jsonResponse(['category' => $updated]);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to update category: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/categories/{id} — deactivate (soft-delete) a category.
     *   Returns 409 if the category is in use by products.
     */
    public function delete(int $id): void
    {
        $categories = $this->dao->listCategories();
        $category   = null;

        foreach ($categories as $cat) {
            if ($cat['id'] == $id) {
                $category = $cat;
                break;
            }
        }

        if (!$category) {
            $this->errorResponse('Category not found', 404);
            return;
        }

        $p     = $this->db->getTablePrefix();
        $usage = $this->db->query(
            "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE category_id = :category_id",
            ['category_id' => $id]
        );

        if ($usage[0]['count'] > 0) {
            $this->errorResponse('Cannot delete category that is in use by products', 409);
            return;
        }

        try {
            $this->dao->upsertCategory(
                $category['code'],
                $category['label'],
                $category['description'] ?? '',
                $category['sort_order'] ?? 0,
                false
            );

            $this->jsonResponse(['message' => 'Category deactivated']);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to deactivate category: ' . $e->getMessage(), 500);
        }
    }
}
