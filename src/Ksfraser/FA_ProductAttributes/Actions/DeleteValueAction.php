<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Deletes or soft-deactivates an attribute value.
 *
 * Hard-deletes if not used by any products; deactivates otherwise.
 */
class DeleteValueAction
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao       = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     * @throws \Exception if value_id is invalid or value not found
     */
    public function handle(array $postData): string
    {
        $valueId    = (int)($postData['value_id'] ?? 0);
        $categoryId = (int)($postData['category_id'] ?? 0);

        if ($valueId <= 0) {
            throw new \Exception("Value ID is required");
        }

        // Find value details
        $values = $this->dao->listValues($categoryId);
        $value  = null;
        foreach ($values as $v) {
            if ((int)$v['id'] === $valueId) {
                $value = $v;
                break;
            }
        }

        if ($value === null) {
            throw new \Exception("Value not found");
        }

        // Check if in use
        $p     = $this->dbAdapter->getTablePrefix();
        $rows  = $this->dbAdapter->query(
            "SELECT COUNT(*) as count FROM `{$p}product_attribute_assignments` WHERE value_id = :value_id",
            ['value_id' => $valueId]
        );
        $count = (int)($rows[0]['count'] ?? 0);

        if ($count > 0) {
            // Soft-delete: deactivate
            $this->dao->upsertValue(
                $categoryId,
                (string)$value['value'],
                (string)$value['slug'],
                (int)($value['sort_order'] ?? 0),
                false,
                $valueId
            );
            return "Value '{$value['value']}' deactivated successfully (in use by products)";
        }

        // Hard-delete
        $this->dao->deleteValue($valueId);
        return "Value '{$value['value']}' deleted successfully";
    }
}
