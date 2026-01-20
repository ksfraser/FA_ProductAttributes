<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class VariationService
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db)
    {
        $this->dao = $dao;
        $this->db = $db;
    }

    /**
     * Generate all possible variation combinations for a product
     * @param string $stockId The parent product stock ID
     * @return array<int, array<string, mixed>> Array of variation data
     */
    public function generateVariations(string $stockId): array
    {
        $assignments = $this->dao->listAssignments($stockId);
        
        // Group assignments by category, ordered by royal order
        $categories = [];
        foreach ($assignments as $assignment) {
            $categoryId = $assignment['category_id'];
            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'code' => $assignment['category_code'],
                    'label' => $assignment['category_label'],
                    'sort_order' => $assignment['category_sort_order'] ?? 0, // Need to add this to query
                    'values' => []
                ];
            }
            $categories[$categoryId]['values'][] = [
                'id' => $assignment['value_id'],
                'value' => $assignment['value_label'],
                'slug' => $assignment['value_slug'],
                'category_code' => $assignment['category_code']
            ];
        }

        // Sort categories by royal order (sort_order)
        usort($categories, function($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        // Generate all combinations
        $combinations = $this->generateCombinations(array_values($categories));
        
        $variations = [];
        foreach ($combinations as $combination) {
            $variationStockId = $this->generateVariationStockId($stockId, $combination);
            $variationDescription = $this->generateVariationDescription($stockId, $combination);
            
            $variations[] = [
                'stock_id' => $variationStockId,
                'description' => $variationDescription,
                'combination' => $combination
            ];
        }

        return $variations;
    }

    /**
     * Generate all combinations of attribute values
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function generateCombinations(array $categories): array
    {
        if (empty($categories)) {
            return [[]];
        }

        $combinations = [[]];
        
        foreach ($categories as $category) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($category['values'] as $value) {
                    $newCombinations[] = array_merge($combination, [$value]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    /**
     * Generate stock ID for variation
     * @param string $parentStockId
     * @param array<int, array<string, mixed>> $combination
     * @return string
     */
    private function generateVariationStockId(string $parentStockId, array $combination): string
    {
        $suffixes = [];
        foreach ($combination as $value) {
            $suffixes[] = strtoupper($value['slug']);
        }
        
        return $parentStockId . '-' . implode('-', $suffixes);
    }

    /**
     * Generate description for variation by replacing placeholders
     * @param string $parentStockId
     * @param array<int, array<string, mixed>> $combination
     * @return string
     */
    private function generateVariationDescription(string $parentStockId, array $combination): string
    {
        // Get parent description from FA database
        // This is a placeholder - we'll need to integrate with FA's database
        $parentDescription = $this->getParentDescription($parentStockId);
        
        // Replace ${ATTRIB_CLASS} placeholders
        $replacements = [];
        foreach ($combination as $value) {
            $categoryCode = strtoupper($value['category_code'] ?? 'ATTRIB');
            $placeholder = '${' . $categoryCode . '}';
            $replacements[$placeholder] = $value['value'];
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $parentDescription);
    }

    /**
     * Get parent product description
     * @param string $stockId
     * @return string
     */
    protected function getParentDescription(string $stockId): string
    {
        // Query FA's stock_master table for the product description
        $p = $this->db->getTablePrefix();
        $results = $this->db->selectAll(
            "SELECT description FROM {$p}stock_master WHERE stock_id = :stock_id",
            ['stock_id' => $stockId]
        );

        if (empty($results)) {
            // If product doesn't exist, return a generic description
            return "Product {$stockId}";
        }

        return $results[0]['description'];
    }
}