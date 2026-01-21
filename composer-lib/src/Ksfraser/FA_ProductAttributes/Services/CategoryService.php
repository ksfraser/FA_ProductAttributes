<?php

namespace Ksfraser\FA_ProductAttributes\Services;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * Service class for category business logic
 *
 * Handles validation, business rules, and data operations for product attribute categories.
 * Follows Single Responsibility Principle by encapsulating all category-related business logic.
 *
 * @package Ksfraser\FA_ProductAttributes\Services
 */
class CategoryService
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
     * Create a new category
     *
     * @param array $data Category data (code, label, description, sort_order, active)
     * @return array Created category data
     * @throws \InvalidArgumentException When validation fails
     */
    public function createCategory(array $data): array
    {
        $this->validateCategoryData($data, false);

        $code = trim($data['code']);
        $label = trim($data['label']);
        $description = trim($data['description'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $active = (bool)($data['active'] ?? true);

        // Check for duplicate code
        $existingCategories = $this->dao->listCategories();
        foreach ($existingCategories as $category) {
            if ($category['code'] === $code) {
                throw new \InvalidArgumentException("Category code '$code' already exists. Use update to modify it.");
            }
        }

        $this->dao->upsertCategory($code, $label, $description, $sortOrder, $active);

        // Return the created category
        $categories = $this->dao->listCategories();
        foreach ($categories as $category) {
            if ($category['code'] === $code) {
                return $category;
            }
        }

        throw new \RuntimeException('Failed to retrieve created category');
    }

    /**
     * Update an existing category
     *
     * @param int $categoryId Category ID to update
     * @param array $data Updated category data
     * @return array Updated category data
     * @throws \InvalidArgumentException When validation fails or category not found
     */
    public function updateCategory(int $categoryId, array $data): array
    {
        $this->validateCategoryData($data, true);

        $code = trim($data['code']);
        $label = trim($data['label']);
        $description = trim($data['description'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $active = (bool)($data['active'] ?? true);

        // Find existing category
        $existingCategories = $this->dao->listCategories();
        $existingCategory = null;
        foreach ($existingCategories as $category) {
            if ((int)$category['id'] === $categoryId) {
                $existingCategory = $category;
                break;
            }
        }

        if (!$existingCategory) {
            throw new \InvalidArgumentException('Category not found');
        }

        // Check for duplicate code if code changed
        if ($existingCategory['code'] !== $code) {
            foreach ($existingCategories as $category) {
                if ((int)$category['id'] !== $categoryId && $category['code'] === $code) {
                    throw new \InvalidArgumentException("Category code '$code' already exists");
                }
            }
        }

        $this->dao->upsertCategory($code, $label, $description, $sortOrder, $active, $categoryId);

        // Return the updated category
        $categories = $this->dao->listCategories();
        foreach ($categories as $category) {
            if ((int)$category['id'] === $categoryId) {
                return $category;
            }
        }

        throw new \RuntimeException('Failed to retrieve updated category');
    }

    /**
     * Delete a category
     *
     * Performs hard delete if category is not in use by products,
     * otherwise performs soft delete (deactivation).
     *
     * @param int $categoryId Category ID to delete
     * @return array Delete result with status and message
     * @throws \InvalidArgumentException When category not found
     */
    public function deleteCategory(int $categoryId): array
    {
        // Find the category
        $categories = $this->dao->listCategories();
        $categoryToDelete = null;
        foreach ($categories as $category) {
            if ((int)$category['id'] === $categoryId) {
                $categoryToDelete = $category;
                break;
            }
        }

        if (!$categoryToDelete) {
            throw new \InvalidArgumentException('Category not found');
        }

        // Check if category is in use by products
        $tablePrefix = $this->dbAdapter->getTablePrefix();
        $usage = $this->dbAdapter->query(
            "SELECT COUNT(*) as count FROM `{$tablePrefix}product_attribute_assignments` WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );

        if ($usage[0]['count'] > 0) {
            // Category is in use - soft delete
            $this->dao->upsertCategory(
                $categoryToDelete['code'],
                $categoryToDelete['label'],
                $categoryToDelete['description'] ?? '',
                $categoryToDelete['sort_order'] ?? 0,
                false, // Deactivate
                $categoryId
            );

            return [
                'deleted' => true,
                'hard_delete' => false,
                'message' => 'Category deactivated successfully (in use by products)'
            ];
        } else {
            // Category is not in use - hard delete
            $this->dao->deleteCategory($categoryId);

            return [
                'deleted' => true,
                'hard_delete' => true,
                'message' => 'Category deleted successfully'
            ];
        }
    }

    /**
     * Get all categories
     *
     * @return array List of all categories
     */
    public function getAllCategories(): array
    {
        return $this->dao->listCategories();
    }

    /**
     * Get a single category by ID
     *
     * @param int $categoryId Category ID
     * @return array|null Category data or null if not found
     */
    public function getCategory(int $categoryId): ?array
    {
        $categories = $this->dao->listCategories();
        foreach ($categories as $category) {
            if ((int)$category['id'] === $categoryId) {
                return $category;
            }
        }
        return null;
    }

    /**
     * Validate category data
     *
     * @param array $data Category data to validate
     * @param bool $isUpdate Whether this is an update operation
     * @throws \InvalidArgumentException When validation fails
     */
    private function validateCategoryData(array $data, bool $isUpdate): void
    {
        $code = trim($data['code'] ?? '');
        $label = trim($data['label'] ?? '');

        if (empty($code)) {
            throw new \InvalidArgumentException('Code is required');
        }

        if (empty($label)) {
            throw new \InvalidArgumentException('Label is required');
        }

        // Additional validation can be added here
        if (strlen($code) > 50) {
            throw new \InvalidArgumentException('Code must be 50 characters or less');
        }

        if (strlen($label) > 100) {
            throw new \InvalidArgumentException('Label must be 100 characters or less');
        }
    }
}