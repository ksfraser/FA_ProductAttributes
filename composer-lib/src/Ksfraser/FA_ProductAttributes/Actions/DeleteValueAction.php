<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class DeleteValueAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): string
    {
        try {
            $valueId = (int)($postData['value_id'] ?? 0);
            $categoryId = (int)($postData['category_id'] ?? 0);

            display_notification("DeleteValueAction: value_id=$valueId, category_id=$categoryId");

            if ($valueId <= 0) {
                throw new \Exception("Value ID is required");
            }

            // Get the value to show in confirmation
            $values = $this->dao->listValues($categoryId);
            $valueToDelete = null;
            foreach ($values as $v) {
                if ((int)$v['id'] === $valueId) {
                    $valueToDelete = $v;
                    break;
                }
            }

            if (!$valueToDelete) {
                throw new \Exception("Value not found");
            }

            // Soft delete by deactivating
            $this->dao->upsertValue(
                $categoryId,
                $valueToDelete['value'],
                $valueToDelete['slug'],
                $valueToDelete['sort_order'],
                false // Deactivate
            );

            return sprintf(_("Value '%s' deactivated successfully"), $valueToDelete['value']);
        } catch (\Exception $e) {
            display_error("Error deleting value: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}