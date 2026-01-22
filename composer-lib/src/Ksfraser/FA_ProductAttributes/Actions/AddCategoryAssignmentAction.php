<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class AddCategoryAssignmentAction
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
            $this->dao->addCategoryAssignment($stockId, $categoryId);
            return _("Added category assignment");
        }

        return _("Invalid category assignment data");
    }
}