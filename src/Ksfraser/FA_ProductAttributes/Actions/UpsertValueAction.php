<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Creates or updates an attribute value.
 *
 * Prevents duplicate values within a category, validates required fields.
 */
class UpsertValueAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     * @throws \Exception on validation failures
     */
    public function handle(array $postData): string
    {
        $categoryId = (int)($postData['category_id'] ?? 0);
        $value      = trim((string)($postData['value'] ?? ''));
        $slug       = trim((string)($postData['slug'] ?? ''));
        $sortOrder  = (int)($postData['sort_order'] ?? 0);
        $active     = isset($postData['active']) && $postData['active'] !== '';
        $valueId    = (int)($postData['value_id'] ?? 0);

        if ($categoryId <= 0) {
            throw new \Exception("Category ID is required");
        }

        if ($value === '') {
            throw new \Exception("Value is required");
        }

        // Duplicate-value check within the same category
        $values = $this->dao->listValues($categoryId);
        foreach ($values as $v) {
            $existingId = (int)$v['id'];
            if (strtolower((string)$v['value']) === strtolower($value) && $existingId !== $valueId) {
                if ($valueId > 0) {
                    throw new \Exception("Value '{$value}' already exists in this category");
                }
                throw new \Exception("Value '{$value}' already exists in this category. Use Edit to modify it.");
            }
        }

        $this->dao->upsertValue($categoryId, $value, $slug, $sortOrder, $active, $valueId);

        return $valueId > 0 ? "Value updated successfully" : "Value saved successfully";
    }
}
