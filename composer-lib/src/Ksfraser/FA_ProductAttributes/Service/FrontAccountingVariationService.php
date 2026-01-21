<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class FrontAccountingVariationService extends VariationService
{
    /** @var DbAdapterInterface */
    private $faDb;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $attributesDb, DbAdapterInterface $faDb)
    {
        parent::__construct($dao, $attributesDb);
        $this->faDb = $faDb;
    }

    /**
     * Create variations in FrontAccounting database
     * @param string $parentStockId
     * @param bool $copyPricing
     * @return array<int, string> Array of created variation stock IDs
     */
    public function createVariations(string $parentStockId, bool $copyPricing = false): array
    {
        $variations = $this->generateVariations($parentStockId);
        $createdVariations = [];

        foreach ($variations as $variation) {
            $this->createVariationInFA($parentStockId, $variation, $copyPricing);
            $createdVariations[] = $variation['stock_id'];
        }

        return $createdVariations;
    }

    /**
     * Create a single variation in FA database
     * @param string $parentStockId
     * @param array<string, mixed> $variation
     * @param bool $copyPricing
     */
    private function createVariationInFA(string $parentStockId, array $variation, bool $copyPricing): void
    {
        $p = $this->faDb->getTablePrefix();

        // Get parent product data
        $parentData = $this->faDb->query(
            "SELECT * FROM {$p}stock_master WHERE stock_id = :stock_id",
            ['stock_id' => $parentStockId]
        );

        if (empty($parentData)) {
            throw new \RuntimeException("Parent product {$parentStockId} not found");
        }

        $parent = $parentData[0];

        // Insert variation
        $this->faDb->execute(
            "INSERT INTO {$p}stock_master (
                stock_id, category_id, description, long_description, units, mb_flag,
                sales_account, cogs_account, inventory_account, adjustment_account,
                wip_account, dimension_id, dimension2_id, tax_type_id, sales_tax_included,
                base_sales_price, material_cost, labour_cost, overhead_cost,
                last_cost, actual_cost, inactive, no_sale, editable, parent_stock_id
            ) VALUES (
                :stock_id, :category_id, :description, :long_description, :units, :mb_flag,
                :sales_account, :cogs_account, :inventory_account, :adjustment_account,
                :wip_account, :dimension_id, :dimension2_id, :tax_type_id, :sales_tax_included,
                :base_sales_price, :material_cost, :labour_cost, :overhead_cost,
                :last_cost, :actual_cost, :inactive, :no_sale, :editable, :parent_stock_id
            )",
            [
                'stock_id' => $variation['stock_id'],
                'category_id' => $parent['category_id'],
                'description' => $variation['description'],
                'long_description' => $variation['description'], // Could be different
                'units' => $parent['units'],
                'mb_flag' => $parent['mb_flag'],
                'sales_account' => $parent['sales_account'],
                'cogs_account' => $parent['cogs_account'],
                'inventory_account' => $parent['inventory_account'],
                'adjustment_account' => $parent['adjustment_account'],
                'wip_account' => $parent['wip_account'],
                'dimension_id' => $parent['dimension_id'],
                'dimension2_id' => $parent['dimension2_id'],
                'tax_type_id' => $parent['tax_type_id'],
                'sales_tax_included' => $parent['sales_tax_included'],
                'base_sales_price' => $parent['base_sales_price'],
                'material_cost' => $parent['material_cost'],
                'labour_cost' => $parent['labour_cost'],
                'overhead_cost' => $parent['overhead_cost'],
                'last_cost' => $parent['last_cost'],
                'actual_cost' => $parent['actual_cost'],
                'inactive' => 0, // Variations start active
                'no_sale' => $parent['no_sale'],
                'editable' => $parent['editable'],
                'parent_stock_id' => $parentStockId
            ]
        );

        // Copy pricing if requested
        if ($copyPricing) {
            $this->copyPricing($parentStockId, $variation['stock_id']);
        }
    }

    /**
     * Copy pricing from parent to variation
     * @param string $parentStockId
     * @param string $variationStockId
     */
    private function copyPricing(string $parentStockId, string $variationStockId): void
    {
        $p = $this->faDb->getTablePrefix();

        // Get parent prices
        $parentPrices = $this->faDb->query(
            "SELECT * FROM {$p}prices WHERE stock_id = :stock_id",
            ['stock_id' => $parentStockId]
        );

        // Insert prices for variation
        foreach ($parentPrices as $price) {
            $this->faDb->execute(
                "INSERT INTO {$p}prices (stock_id, sales_type_id, curr_abrev, price) 
                 VALUES (:stock_id, :sales_type_id, :curr_abrev, :price)",
                [
                    'stock_id' => $variationStockId,
                    'sales_type_id' => $price['sales_type_id'],
                    'curr_abrev' => $price['curr_abrev'],
                    'price' => $price['price']
                ]
            );
        }
    }

    /**
     * Get parent product description from FA database
     * @param string $stockId
     * @return string
     */
    protected function getParentDescription(string $stockId): string
    {
        $p = $this->faDb->getTablePrefix();
        $result = $this->faDb->query(
            "SELECT description FROM {$p}stock_master WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );

        return $result[0]['description'] ?? '';
    }
}