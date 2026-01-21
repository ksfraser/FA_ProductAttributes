<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class UpsertValueAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): string
    {
        $categoryId = (int)($postData['category_id'] ?? 0);
        $this->dao->upsertValue(
            $categoryId,
            trim((string)($postData['value'] ?? '')),
            trim((string)($postData['slug'] ?? '')),
            (int)($postData['sort_order'] ?? 0),
            isset($postData['active'])
        );

        return _("Saved value");
    }
}