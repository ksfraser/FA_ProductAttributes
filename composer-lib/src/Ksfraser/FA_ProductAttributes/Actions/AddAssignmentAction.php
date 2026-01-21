<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class AddAssignmentAction
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
        $valueId = (int)($postData['value_id'] ?? 0);
        $sortOrder = (int)($postData['sort_order'] ?? 0);

        if ($stockId !== '' && $categoryId > 0 && $valueId > 0) {
            $this->dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);
            return _("Added assignment");
        }

        return _("Invalid assignment data");
    }
}