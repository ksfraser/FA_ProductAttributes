<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;

class DeleteValueAction
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var FrontAccountingDbAdapter */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, FrontAccountingDbAdapter $dbAdapter = null)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
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

            // Check if value is in use by products
            $p = $this->dbAdapter->getTablePrefix();
            $usage = $this->dbAdapter->query(
                "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE value_id = :value_id",
                ['value_id' => $valueId]
            );

            if ($usage[0]['count'] > 0) {
                // Value is in use - soft delete by deactivating
                $this->dao->upsertValue(
                    $categoryId,
                    $valueToDelete['value'],
                    $valueToDelete['slug'],
                    $valueToDelete['sort_order'],
                    false, // Deactivate
                    $valueId
                );
                return sprintf(_("Value '%s' deactivated successfully (in use by products)"), $valueToDelete['value']);
            } else {
                // Value is not in use - hard delete
                $this->dao->deleteValue($valueId);
                return sprintf(_("Value '%s' deleted successfully"), $valueToDelete['value']);
            }
        } catch (\Exception $e) {
            display_error("Error deleting value: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}