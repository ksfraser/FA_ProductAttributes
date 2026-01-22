<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class UpsertCategoryAction
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(array $postData): string
    {
        try {
            $code = trim((string)($postData['code'] ?? ''));
            $label = trim((string)($postData['label'] ?? ''));
            $description = trim((string)($postData['description'] ?? ''));
            $sortOrder = (int)($postData['sort_order'] ?? 0);
            $active = isset($postData['active']);
            $categoryId = (int)($postData['category_id'] ?? 0);

            display_notification("UpsertCategoryAction: code='$code', label='$label', description='$description', sort_order=$sortOrder, active=" . ($active ? 'true' : 'false') . ", category_id=$categoryId");

            if (empty($code) || empty($label)) {
                throw new \Exception("Code and label are required");
            }

            // Check if this is an update and if the code/label already exists
            if ($categoryId > 0) {
                // This is an update - check if code/label changed
                $existingCategories = $this->dao->listCategories();
                $existingCategory = null;
                foreach ($existingCategories as $c) {
                    if ((int)$c['id'] === $categoryId) {
                        $existingCategory = $c;
                        break;
                    }
                }
                
                if ($existingCategory) {
                    if ($existingCategory['code'] !== $code) {
                        // Code changed - check if new code already exists
                        foreach ($existingCategories as $c) {
                            if ((int)$c['id'] !== $categoryId && $c['code'] === $code) {
                                throw new \Exception("Category code '$code' already exists");
                            }
                        }
                    }
                }
            } else {
                // This is a new category - check if code already exists
                $existingCategories = $this->dao->listCategories();
                foreach ($existingCategories as $c) {
                    if ($c['code'] === $code) {
                        throw new \Exception("Category code '$code' already exists. Use Edit to modify it.");
                    }
                }
            }

            $this->dao->upsertCategory($code, $label, $description, $sortOrder, $active, $categoryId);

            // Debug: check count after save
            $check = $this->dbAdapter->query("SELECT COUNT(*) as cnt FROM `" . $this->dbAdapter->getTablePrefix() . "product_attribute_categories`");
            display_notification("Categories count after save: " . ($check[0]['cnt'] ?? 'error'));

            return $categoryId > 0 ? _("Category updated successfully") : _("Category saved successfully");
        } catch (\Exception $e) {
            display_error("Error saving category: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}