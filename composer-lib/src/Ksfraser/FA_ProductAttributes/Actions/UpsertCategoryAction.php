<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;

class UpsertCategoryAction
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
            $code = trim((string)($postData['code'] ?? ''));
            $label = trim((string)($postData['label'] ?? ''));
            $description = trim((string)($postData['description'] ?? ''));
            $sortOrder = (int)($postData['sort_order'] ?? 0);
            $active = isset($postData['active']);

            display_notification("UpsertCategoryAction: code='$code', label='$label', description='$description', sort_order=$sortOrder, active=" . ($active ? 'true' : 'false'));

            if (empty($code) || empty($label)) {
                throw new \Exception("Code and label are required");
            }

            $this->dao->upsertCategory($code, $label, $description, $sortOrder, $active);

            // Debug: check count after save
            $check = $this->dbAdapter->query("SELECT COUNT(*) as cnt FROM `" . $this->dbAdapter->getTablePrefix() . "product_attribute_categories`");
            display_notification("Categories count after save: " . ($check[0]['cnt'] ?? 'error'));

            return _("Category saved successfully");
        } catch (\Exception $e) {
            display_error("Error saving category: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}