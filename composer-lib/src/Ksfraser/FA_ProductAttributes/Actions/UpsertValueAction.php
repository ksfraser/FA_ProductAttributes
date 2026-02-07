<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;

class UpsertValueAction
{
    /** @var VariationsDao */
    private $dao;

    public function __construct(VariationsDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): string
    {
        try {
            $categoryId = (int)($postData['category_id'] ?? 0);
            $value = trim((string)($postData['value'] ?? ''));
            $slug = trim((string)($postData['slug'] ?? ''));
            $valueId = (int)($postData['value_id'] ?? 0);

            display_notification("UpsertValueAction: category_id=$categoryId, value='$value', slug='$slug', value_id=$valueId");

            if ($categoryId <= 0) {
                throw new \Exception("Category ID is required");
            }
            if (empty($value)) {
                throw new \Exception("Value is required");
            }

            // Check if this is an update and if the value already exists
            if ($valueId > 0) {
                // This is an update - check if value changed
                $existingValues = $this->dao->listValues($categoryId);
                $existingValue = null;
                foreach ($existingValues as $v) {
                    if ((int)$v['id'] === $valueId) {
                        $existingValue = $v;
                        break;
                    }
                }
                
                if ($existingValue && $existingValue['value'] !== $value) {
                    // Value changed - check if new value already exists
                    foreach ($existingValues as $v) {
                        if ((int)$v['id'] !== $valueId && $v['value'] === $value) {
                            throw new \Exception("Value '$value' already exists in this category");
                        }
                    }
                }
            } else {
                // This is a new value - check if it already exists
                $existingValues = $this->dao->listValues($categoryId);
                foreach ($existingValues as $v) {
                    if ($v['value'] === $value) {
                        throw new \Exception("Value '$value' already exists in this category. Use Edit to modify it.");
                    }
                }
            }

            $this->dao->upsertValue(
                $categoryId,
                $value,
                $slug,
                (int)($postData['sort_order'] ?? 0),
                isset($postData['active']),
                $valueId
            );

            return $valueId > 0 ? _("Value updated successfully") : _("Value saved successfully");
        } catch (\Exception $e) {
            display_error("Error saving value: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}