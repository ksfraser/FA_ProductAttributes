<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;

class DeleteCategoryAction
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var FrontAccountingDbAdapter */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, FrontAccountingDbAdapter $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(array $postData): string
    {
        try {
            $categoryId = (int)($postData['category_id'] ?? 0);

            display_notification("DeleteCategoryAction: category_id=$categoryId");

            if ($categoryId <= 0) {
                throw new \Exception("Category ID is required");
            }

            // Get the category to show in confirmation
            $categories = $this->dao->listCategories();
            $categoryToDelete = null;
            foreach ($categories as $c) {
                if ((int)$c['id'] === $categoryId) {
                    $categoryToDelete = $c;
                    break;
                }
            }

            if (!$categoryToDelete) {
                throw new \Exception("Category not found");
            }

            // Check if category is in use
            $p = $this->dbAdapter->getTablePrefix();
            $usage = $this->dbAdapter->query(
                "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE category_id = :category_id",
                ['category_id' => $categoryId]
            );

            if ($usage[0]['count'] > 0) {
                throw new \Exception("Cannot delete category that is in use by products");
            }

            // Soft delete by deactivating
            $this->dao->upsertCategory(
                $categoryToDelete['code'],
                $categoryToDelete['label'],
                $categoryToDelete['description'],
                $categoryToDelete['sort_order'],
                false, // Deactivate
                $categoryId
            );

            return sprintf(_("Category '%s' deactivated successfully"), $categoryToDelete['label']);
        } catch (\Exception $e) {
            display_error("Error deleting category: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}