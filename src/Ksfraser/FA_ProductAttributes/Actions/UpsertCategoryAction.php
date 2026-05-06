<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Creates or updates an attribute category.
 *
 * Prevents duplicate codes, validates required fields.
 */
class UpsertCategoryAction
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
     * @throws \Exception on validation failures
     */
    public function handle(array $postData): string
    {
        $code       = trim((string)($postData['code'] ?? ''));
        $label      = trim((string)($postData['label'] ?? ''));
        $description = trim((string)($postData['description'] ?? ''));
        $sortOrder  = (int)($postData['sort_order'] ?? 0);
        $active     = isset($postData['active']) && $postData['active'] !== '';
        $categoryId = (int)($postData['category_id'] ?? 0);

        if ($code === '' || $label === '') {
            throw new \Exception("Code and label are required");
        }

        // Verify the db table is accessible (validates db connection)
        $p = $this->dbAdapter->getTablePrefix();
        $this->dbAdapter->query("SELECT COUNT(*) as cnt FROM `{$p}product_attribute_categories`");

        // Duplicate-code check
        $categories = $this->dao->listCategories();
        foreach ($categories as $cat) {
            $existingId = (int)$cat['id'];
            if (strtoupper((string)$cat['code']) === strtoupper($code) && $existingId !== $categoryId) {
                if ($categoryId > 0) {
                    throw new \Exception("Category code '{$code}' already exists");
                }
                throw new \Exception("Category code '{$code}' already exists. Use Edit to modify it.");
            }
        }

        $this->dao->upsertCategory($code, $label, $description, $sortOrder, $active, $categoryId);

        return $categoryId > 0 ? "Category updated successfully" : "Category saved successfully";
    }
}
