<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class RemoveCategoryAssignmentAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));
        $categoryId = (int)($postData['category_id'] ?? 0);

        if ($stockId !== '' && $categoryId > 0) {
            $this->dao->removeCategoryAssignment($stockId, $categoryId);
            return _("Removed category assignment");
        }

        return _("Invalid category assignment data");
    }
}