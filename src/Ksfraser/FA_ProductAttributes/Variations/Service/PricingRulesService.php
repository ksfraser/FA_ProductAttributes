<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Service;

class PricingRulesService
{
    /**
     * Apply a single pricing rule to a base price
     * @param float $basePrice
     * @param array<string, mixed> $rule
     * @return float
     */
    public function applyRule(float $basePrice, array $rule): float
    {
        $type = $rule['type'] ?? '';

        switch ($type) {
            case 'fixed':
                return $basePrice + ($rule['amount'] ?? 0);

            case 'percentage':
                $percentage = $rule['amount'] ?? 0;
                return $basePrice + ($basePrice * $percentage / 100);

            case 'combined':
                $fixedAmount = $rule['fixed_amount'] ?? 0;
                $percentage = $rule['percentage'] ?? 0;
                return $basePrice + $fixedAmount + ($basePrice * $percentage / 100);

            default:
                throw new \InvalidArgumentException("Unknown pricing rule type: {$type}");
        }
    }

    /**
     * Apply multiple pricing rules to a base price
     * @param float $basePrice
     * @param array<int, array<string, mixed>> $rules
     * @return float
     */
    public function applyRules(float $basePrice, array $rules): float
    {
        $result = $basePrice;

        foreach ($rules as $rule) {
            $result = $this->applyRule($result, $rule);
        }

        return $result;
    }

    /**
     * Apply pricing rules to variations based on their attribute combinations
     * @param float $basePrice
     * @param array<int, array<string, mixed>> $variations
     * @param array<string, array<string, array<string, mixed>>> $rules
     * @return array<int, array<string, mixed>>
     */
    public function applyRulesToVariations(float $basePrice, array $variations, array $rules): array
    {
        $result = [];

        foreach ($variations as $variation) {
            $calculatedPrice = $basePrice;
            $appliedRules = [];

            // Apply rules for each attribute in the combination
            foreach ($variation['combination'] as $attribute) {
                $categoryCode = $attribute['category_code'];
                $value = $attribute['value'];

                if (isset($rules[$categoryCode][$value])) {
                    $rule = $rules[$categoryCode][$value];
                    $calculatedPrice = $this->applyRule($calculatedPrice, $rule);
                    $appliedRules[] = [
                        'category' => $categoryCode,
                        'value' => $value,
                        'rule' => $rule
                    ];
                }
            }

            $result[] = array_merge($variation, [
                'calculated_price' => $calculatedPrice,
                'applied_rules' => $appliedRules
            ]);
        }

        return $result;
    }
}