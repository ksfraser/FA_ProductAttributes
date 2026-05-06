<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Deletes or soft-deactivates a category.
 *
 * Hard-deletes if not used by any products; deactivates otherwise.
 */
class DeleteCategoryAction
{
    /** @var VariationsDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(VariationsDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao       = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     * @throws \Exception if category_id is invalid or category not found
     */
    public function handle(array $postData): string
    {
        $categoryId = (int)($postData['category_id'] ?? 0);

        if ($categoryId <= 0) {
            throw new \Exception("Category ID is required");
        }

        // Find category details
        $categories = $this->dao->listCategories();
        $category   = null;
        foreach ($categories as $cat) {
            if ((int)$cat['id'] === $categoryId) {
                $category = $cat;
                break;
            }
        }

        if ($category === null) {
            throw new \Exception("Category not found");
        }

        // Check if in use
        $p     = $this->dbAdapter->getTablePrefix();
        $rows  = $this->dbAdapter->query(
            "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE category_id = :category_id",
            ['category_id' => $categoryId]
        );
        $count = (int)($rows[0]['count'] ?? 0);

        if ($count > 0) {
            // Soft-delete: deactivate
            $this->dao->upsertCategory(
                (string)$category['code'],
                (string)$category['label'],
                (string)($category['description'] ?? ''),
                (int)($category['sort_order'] ?? 0),
                false,
                $categoryId
            );
            return "Category '{$category['label']}' deactivated successfully (in use by products)";
        }

        // Hard-delete
        $this->dao->deleteCategory($categoryId);
        return "Category '{$category['label']}' and all its values deleted successfully";
    }
}
