<?php

namespace Ksfraser\FA_ProductAttributes\Services;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * Service class for value business logic
 *
 * Handles validation, business rules, and data operations for product attribute values.
 * Follows Single Responsibility Principle by encapsulating all value-related business logic.
 *
 * @package Ksfraser\FA_ProductAttributes\Services
 */
class ValueService
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
     * Create a new value
     *
     * @param array $data Value data (category_id, value, slug, sort_order, active)
     * @return array Created value data
     * @throws \InvalidArgumentException When validation fails
     */
    public function createValue(array $data): array
    {
        $this->validateValueData($data, false);

        $categoryId = (int)$data['category_id'];
        $value = trim($data['value']);
        $slug = trim($data['slug'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $active = (bool)($data['active'] ?? true);

        // Check for duplicate value in the same category
        $existingValues = $this->dao->listValues($categoryId);
        foreach ($existingValues as $existingValue) {
            if ($existingValue['value'] === $value) {
                throw new \InvalidArgumentException("Value '$value' already exists in this category. Use update to modify it.");
            }
        }

        $this->dao->upsertValue($categoryId, $value, $slug, $sortOrder, $active, 0);

        // Return the created value
        $values = $this->dao->listValues($categoryId);
        foreach ($values as $v) {
            if ($v['value'] === $value) {
                return $v;
            }
        }

        throw new \RuntimeException('Failed to retrieve created value');
    }

    /**
     * Update an existing value
     *
     * @param int $valueId Value ID to update
     * @param array $data Updated value data
     * @return array Updated value data
     * @throws \InvalidArgumentException When validation fails or value not found
     */
    public function updateValue(int $valueId, array $data): array
    {
        $this->validateValueData($data, true);

        $categoryId = (int)$data['category_id'];
        $value = trim($data['value']);
        $slug = trim($data['slug'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $active = (bool)($data['active'] ?? true);

        // Find the existing value
        $existingValues = $this->dao->listValues($categoryId);
        $existingValue = null;
        foreach ($existingValues as $v) {
            if ((int)$v['id'] === $valueId) {
                $existingValue = $v;
                break;
            }
        }

        if (!$existingValue) {
            throw new \InvalidArgumentException('Value not found');
        }

        // If value changed, check for duplicates
        if ($existingValue['value'] !== $value) {
            foreach ($existingValues as $v) {
                if ((int)$v['id'] !== $valueId && $v['value'] === $value) {
                    throw new \InvalidArgumentException("Value '$value' already exists in this category");
                }
            }
        }

        $this->dao->upsertValue($categoryId, $value, $slug, $sortOrder, $active, $valueId);

        // Return the updated value
        $values = $this->dao->listValues($categoryId);
        foreach ($values as $v) {
            if ((int)$v['id'] === $valueId) {
                return $v;
            }
        }

        throw new \RuntimeException('Failed to retrieve updated value');
    }

    /**
     * Delete a value
     *
     * @param int $valueId Value ID to delete
     * @return array Delete result with status and message
     * @throws \InvalidArgumentException When value not found
     */
    public function deleteValue(int $valueId): array
    {
        // Find the value by searching through all categories
        // This is inefficient but necessary since we don't have a direct way to get category_id from value_id
        $categories = $this->dao->listCategories();
        $valueToDelete = null;
        $categoryId = null;

        foreach ($categories as $category) {
            $values = $this->dao->listValues((int)$category['id']);
            foreach ($values as $value) {
                if ((int)$value['id'] === $valueId) {
                    $valueToDelete = $value;
                    $categoryId = (int)$category['id'];
                    break 2;
                }
            }
        }

        if (!$valueToDelete || $categoryId === null) {
            throw new \InvalidArgumentException('Value not found');
        }

        // Check if value is in use by products
        $tablePrefix = $this->dbAdapter->getTablePrefix();
        $usage = $this->dbAdapter->query(
            "SELECT COUNT(*) as count FROM `{$tablePrefix}product_attribute_assignments` WHERE value_id = :value_id",
            ['value_id' => $valueId]
        );

        if ($usage[0]['count'] > 0) {
            // Value is in use - soft delete
            $this->dao->upsertValue(
                (int)$valueToDelete['category_id'],
                $valueToDelete['value'],
                $valueToDelete['slug'] ?? '',
                (int)($valueToDelete['sort_order'] ?? 0),
                false, // Deactivate
                $valueId
            );

            return [
                'deleted' => true,
                'hard_delete' => false,
                'message' => 'Value deactivated successfully (in use by products)'
            ];
        } else {
            // Value is not in use - hard delete
            $this->dao->deleteValue($valueId);

            return [
                'deleted' => true,
                'hard_delete' => true,
                'message' => 'Value deleted successfully'
            ];
        }
    }

    /**
     * Get all values for a category
     *
     * @param int $categoryId Category ID
     * @return array List of values for the category
     */
    public function getValuesByCategory(int $categoryId): array
    {
        return $this->dao->listValues($categoryId);
    }

    /**
     * Validate value data
     *
     * @param array $data Value data to validate
     * @param bool $isUpdate Whether this is an update operation
     * @throws \InvalidArgumentException When validation fails
     */
    private function validateValueData(array $data, bool $isUpdate): void
    {
        $categoryId = (int)($data['category_id'] ?? 0);
        $value = trim($data['value'] ?? '');

        if ($categoryId <= 0) {
            throw new \InvalidArgumentException('Category ID is required');
        }

        if (empty($value)) {
            throw new \InvalidArgumentException('Value is required');
        }

        // Additional validation can be added here
        if (strlen($value) > 100) {
            throw new \InvalidArgumentException('Value must be 100 characters or less');
        }
    }
}