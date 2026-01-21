<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;

class UpsertCategoryAction
{
    private ProductAttributesDao $dao;
    private FrontAccountingDbAdapter $dbAdapter;

    public function __construct(ProductAttributesDao $dao, FrontAccountingDbAdapter $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(array $postData): string
    {
        $this->dao->upsertCategory(
            trim((string)($postData['code'] ?? '')),
            trim((string)($postData['label'] ?? '')),
            trim((string)($postData['description'] ?? '')),
            (int)($postData['sort_order'] ?? 0),
            isset($postData['active'])
        );

        // Debug: check count after save
        $check = $this->dbAdapter->query("SELECT COUNT(*) as cnt FROM `" . $this->dbAdapter->getTablePrefix() . "product_attribute_categories`");
        display_notification("Categories count after save: " . ($check[0]['cnt'] ?? 'error'));

        return _("Saved category");
    }
}