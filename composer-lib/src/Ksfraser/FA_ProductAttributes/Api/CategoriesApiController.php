<?php

namespace Ksfraser\FA_ProductAttributes\Api;

/**
 * REST API controller for attribute categories
 */
class CategoriesApiController extends BaseApiController
{
    /**
     * GET /api/categories - List all categories
     */
    public function index(): void
    {
        $categories = $this->dao->listCategories();
        $this->jsonResponse(['categories' => $categories]);
    }

    /**
     * GET /api/categories/{id} - Get single category
     * @param int $id
     */
    public function show(int $id): void
    {
        $categories = $this->dao->listCategories();
        $category = null;

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
     * POST /api/categories - Create new category
     */
    public function create(): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['code', 'label'])) {
            $this->errorResponse('Missing required fields: code, label');
        }

        try {
            $this->dao->upsertCategory(
                $data['code'],
                $data['label'],
                $data['description'] ?? '',
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            // Get the created category
            $categories = $this->dao->listCategories();
            $created = null;
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
     * PUT /api/categories/{id} - Update category
     * @param int $id
     */
    public function update(int $id): void
    {
        $data = $this->getJsonInput();

        if (!$this->validateRequired($data, ['code', 'label'])) {
            $this->errorResponse('Missing required fields: code, label');
        }

        try {
            $this->dao->upsertCategory(
                $data['code'],
                $data['label'],
                $data['description'] ?? '',
                $data['sort_order'] ?? 0,
                $data['active'] ?? true
            );

            // Get the updated category
            $categories = $this->dao->listCategories();
            $updated = null;
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
     * DELETE /api/categories/{id} - Delete category
     * @param int $id
     */
    public function delete(int $id): void
    {
        // Check if category exists
        $categories = $this->dao->listCategories();
        $category = null;

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

        // Check if category is in use
        $p = $this->db->getTablePrefix();
        $usage = $this->db->query(
            "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE category_id = :category_id",
            ['category_id' => $id]
        );

        if ($usage[0]['count'] > 0) {
            $this->errorResponse('Cannot delete category that is in use by products', 409);
            return;
        }

        try {
            // Soft delete by deactivating the category
            $this->dao->upsertCategory(
                $category['code'],
                $category['label'],
                $category['description'],
                $category['sort_order'],
                false // Deactivate instead of delete
            );

            $this->jsonResponse(['message' => 'Category deactivated']);
        } catch (\Exception $e) {
            $this->errorResponse('Failed to deactivate category: ' . $e->getMessage(), 500);
        }
    }
}