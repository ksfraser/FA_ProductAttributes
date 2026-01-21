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
        try {
            $categoryId = (int)($postData['category_id'] ?? 0);
            $value = trim((string)($postData['value'] ?? ''));
            $slug = trim((string)($postData['slug'] ?? ''));

            display_notification("UpsertValueAction: category_id=$categoryId, value='$value', slug='$slug'");

            if ($categoryId <= 0) {
                throw new \Exception("Category ID is required");
            }
            if (empty($value)) {
                throw new \Exception("Value is required");
            }

            $this->dao->upsertValue(
                $categoryId,
                $value,
                $slug,
                (int)($postData['sort_order'] ?? 0),
                isset($postData['active'])
            );

            return _("Value saved successfully");
        } catch (\Exception $e) {
            display_error("Error saving value: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}