<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class RetroactiveApplicationService
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $faDb;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $faDb)
    {
        $this->dao = $dao;
        $this->faDb = $faDb;
    }

    /**
     * Scan existing products for variation patterns
     * @return array<string, array<string, mixed>> Suggested parent-child relationships
     */
    public function scanForVariations(): array
    {
        $products = $this->getAllStockIds();
        $patterns = $this->identifyPatterns($products);
        $suggestions = [];

        foreach ($patterns as $pattern => $stockIds) {
            if (count($stockIds) >= 2) { // Need at least 2 variations for a pattern
                $suggestion = $this->analyzePattern($pattern, $stockIds);
                if ($suggestion) {
                    $suggestions[$pattern] = $suggestion;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Get all stock IDs from FA database
     * @return array<int, string>
     */
    private function getAllStockIds(): array
    {
        $p = $this->faDb->getTablePrefix();
        $result = $this->faDb->selectAll("SELECT stock_id FROM {$p}stock_master ORDER BY stock_id");
        return array_column($result, 'stock_id');
    }

    /**
     * Identify potential variation patterns in stock IDs
     * @param array<int, string> $stockIds
     * @return array<string, array<int, string>>
     */
    private function identifyPatterns(array $stockIds): array
    {
        $patterns = [];

        foreach ($stockIds as $stockId) {
            // Look for patterns like: BASE-ATTRIB1-ATTRIB2
            // Split on common separators
            $parts = preg_split('/[-_]/', $stockId);
            
            if (count($parts) >= 2) {
                // Try different base lengths
                for ($baseLength = 1; $baseLength < count($parts); $baseLength++) {
                    $base = implode('-', array_slice($parts, 0, $baseLength));
                    $attributes = array_slice($parts, $baseLength);
                    
                    if (!empty($attributes)) {
                        $patternKey = $base . '-*';
                        if (!isset($patterns[$patternKey])) {
                            $patterns[$patternKey] = [];
                        }
                        $patterns[$patternKey][] = $stockId;
                    }
                }
            }
        }

        // Filter patterns with multiple matches
        return array_filter($patterns, function($stockIds) {
            return count($stockIds) >= 2;
        });
    }

    /**
     * Analyze a pattern and suggest attribute structure
     * @param string $pattern
     * @param array<int, string> $stockIds
     * @return array<string, mixed>|null
     */
    private function analyzePattern(string $pattern, array $stockIds): ?array
    {
        $base = str_replace('-*', '', $pattern);
        $attributeParts = [];

        // Extract attribute parts from each stock ID
        foreach ($stockIds as $stockId) {
            if (strpos($stockId, $base . '-') === 0) {
                $attributes = substr($stockId, strlen($base) + 1);
                $attributeParts[] = explode('-', $attributes);
            }
        }

        if (empty($attributeParts)) {
            return null;
        }

        // Check if all variations have the same number of attributes
        $attributeCount = count($attributeParts[0]);
        $consistent = true;
        foreach ($attributeParts as $parts) {
            if (count($parts) !== $attributeCount) {
                $consistent = false;
                break;
            }
        }

        if (!$consistent) {
            return null;
        }

        // Group by attribute position
        $attributeGroups = [];
        for ($i = 0; $i < $attributeCount; $i++) {
            $attributeGroups[$i] = [];
            foreach ($attributeParts as $parts) {
                $attributeGroups[$i][] = $parts[$i];
            }
            $attributeGroups[$i] = array_unique($attributeGroups[$i]);
            sort($attributeGroups[$i]);
        }

        return [
            'base_stock_id' => $base,
            'existing_variations' => $stockIds,
            'attribute_groups' => $attributeGroups,
            'suggested_categories' => $this->suggestCategories($attributeGroups),
            'confidence' => $this->calculateConfidence($stockIds, $attributeGroups)
        ];
    }

    /**
     * Suggest attribute categories based on attribute values
     * @param array<int, array<int, string>> $attributeGroups
     * @return array<int, array<string, mixed>>
     */
    private function suggestCategories(array $attributeGroups): array
    {
        $categories = [];
        $allCategories = $this->dao->listCategories();

        foreach ($attributeGroups as $position => $values) {
            $suggestedCategory = $this->findBestCategoryMatch($values, $allCategories);
            $categories[$position] = [
                'position' => $position,
                'values' => $values,
                'suggested_category' => $suggestedCategory,
                'suggested_values' => $this->suggestValues($values, $suggestedCategory)
            ];
        }

        return $categories;
    }

    /**
     * Find best category match for a set of values
     * @param array<int, string> $values
     * @param array<int, array<string, mixed>> $allCategories
     * @return array<string, mixed>|null
     */
    private function findBestCategoryMatch(array $values, array $allCategories): ?array
    {
        // Simple heuristic: look for categories that might match the values
        // This could be enhanced with more sophisticated matching
        foreach ($allCategories as $category) {
            $categoryValues = $this->dao->listValues($category['id']);
            $categoryValueLabels = array_column($categoryValues, 'value');
            
            // Check if any of our values match existing category values
            $matches = array_intersect($values, $categoryValueLabels);
            if (!empty($matches)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Suggest values for a category
     * @param array<int, string> $values
     * @param array<string, mixed>|null $category
     * @return array<int, array<string, mixed>>
     */
    private function suggestValues(array $values, ?array $category): array
    {
        $suggestedValues = [];

        if ($category) {
            $existingValues = $this->dao->listValues($category['id']);
            $existingValueLabels = array_column($existingValues, 'value');
            
            foreach ($values as $value) {
                $existing = array_search($value, $existingValueLabels);
                if ($existing !== false) {
                    $suggestedValues[] = $existingValues[$existing];
                } else {
                    // Suggest creating new value
                    $suggestedValues[] = [
                        'id' => null, // New value
                        'value' => $value,
                        'slug' => $this->createSlug($value),
                        'category_id' => $category['id']
                    ];
                }
            }
        } else {
            // No category match, suggest new values
            foreach ($values as $value) {
                $suggestedValues[] = [
                    'id' => null,
                    'value' => $value,
                    'slug' => $this->createSlug($value),
                    'category_id' => null // New category needed
                ];
            }
        }

        return $suggestedValues;
    }

    /**
     * Calculate confidence score for the pattern
     * @param array<int, string> $stockIds
     * @param array<int, array<int, string>> $attributeGroups
     * @return float
     */
    private function calculateConfidence(array $stockIds, array $attributeGroups): float
    {
        $totalVariations = count($stockIds);
        $expectedVariations = 1;
        
        foreach ($attributeGroups as $group) {
            $expectedVariations *= count($group);
        }

        // Higher confidence if actual matches expected
        if ($totalVariations === $expectedVariations) {
            return 1.0;
        }

        // Partial matches get lower confidence
        return min($totalVariations / $expectedVariations, 1.0);
    }

    /**
     * Create a slug from a value
     * @param string $value
     * @return string
     */
    private function createSlug(string $value): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }

    /**
     * Apply suggested assignments
     * @param array<string, mixed> $suggestion
     * @return bool
     */
    public function applySuggestion(array $suggestion): bool
    {
        if (!isset($suggestion['base_stock_id']) || empty($suggestion['existing_variations'])) {
            return false;
        }

        $baseStockId = $suggestion['base_stock_id'];

        try {
            // Apply the suggested categories and values
            if (isset($suggestion['suggested_categories'])) {
                foreach ($suggestion['suggested_categories'] as $position => $categorySuggestion) {
                    if ($categorySuggestion['suggested_category'] && !empty($categorySuggestion['suggested_values'])) {
                        $categoryId = $categorySuggestion['suggested_category']['id'];

                        foreach ($categorySuggestion['suggested_values'] as $valueSuggestion) {
                            if ($valueSuggestion['suggested_value']) {
                                $valueId = $valueSuggestion['suggested_value']['id'];

                                // Add assignment for this position
                                $this->dao->addAssignment($baseStockId, $categoryId, $valueId, $position);
                            }
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            // Log error if logging is available
            return false;
        }
    }
}