<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\Service;

use Ksfraser\FA_ProductAttributes\Variations\Service\PricingRulesService;
use PHPUnit\Framework\TestCase;

class PricingRulesServiceTest extends TestCase
{
    public function testApplyFixedAmountAdjustment(): void
    {
        $service = new PricingRulesService();

        $basePrice = 10.00;
        $rule = [
            'type' => 'fixed',
            'amount' => 2.50
        ];

        $result = $service->applyRule($basePrice, $rule);

        $this->assertEquals(12.50, $result);
    }

    public function testApplyPercentageAdjustment(): void
    {
        $service = new PricingRulesService();

        $basePrice = 10.00;
        $rule = [
            'type' => 'percentage',
            'amount' => 25.0
        ];

        $result = $service->applyRule($basePrice, $rule);

        $this->assertEquals(12.50, $result);
    }

    public function testApplyCombinedAdjustment(): void
    {
        $service = new PricingRulesService();

        $basePrice = 10.00;
        $rule = [
            'type' => 'combined',
            'fixed_amount' => 2.00,
            'percentage' => 10.0
        ];

        $result = $service->applyRule($basePrice, $rule);

        // Should be: 10.00 + 2.00 + (10.00 * 0.10) = 13.00
        $this->assertEquals(13.00, $result);
    }

    public function testApplyMultipleRules(): void
    {
        $service = new PricingRulesService();

        $basePrice = 10.00;
        $rules = [
            ['type' => 'fixed', 'amount' => 1.00],      // +1.00 = 11.00
            ['type' => 'percentage', 'amount' => 10.0]  // +1.10 = 12.10
        ];

        $result = $service->applyRules($basePrice, $rules);

        $this->assertEquals(12.10, $result);
    }

    public function testApplyRulesToVariations(): void
    {
        $service = new PricingRulesService();

        $basePrice = 20.00;
        $variations = [
            [
                'stock_id' => 'TSHIRT-S-RED',
                'combination' => [
                    ['category_code' => 'SIZE', 'value' => 'Small'],
                    ['category_code' => 'COLOR', 'value' => 'Red']
                ]
            ],
            [
                'stock_id' => 'TSHIRT-L-BLUE',
                'combination' => [
                    ['category_code' => 'SIZE', 'value' => 'Large'],
                    ['category_code' => 'COLOR', 'value' => 'Blue']
                ]
            ]
        ];

        $rules = [
            'SIZE' => [
                'Small' => ['type' => 'fixed', 'amount' => -2.00],  // Discount for small
                'Large' => ['type' => 'fixed', 'amount' => 3.00]    // Surcharge for large
            ],
            'COLOR' => [
                'Red' => ['type' => 'percentage', 'amount' => 5.0],  // 5% for red
                'Blue' => ['type' => 'percentage', 'amount' => 0.0]  // No change for blue
            ]
        ];

        $result = $service->applyRulesToVariations($basePrice, $variations, $rules);

        $this->assertCount(2, $result);

        // TSHIRT-S-RED: 20.00 - 2.00 + (18.00 * 0.05) = 18.90
        $this->assertEquals(18.90, $result[0]['calculated_price']);
        $this->assertEquals('TSHIRT-S-RED', $result[0]['stock_id']);

        // TSHIRT-L-BLUE: 20.00 + 3.00 + (23.00 * 0.00) = 23.00
        $this->assertEquals(23.00, $result[1]['calculated_price']);
        $this->assertEquals('TSHIRT-L-BLUE', $result[1]['stock_id']);
    }

    public function testInvalidRuleType(): void
    {
        $service = new PricingRulesService();

        $basePrice = 10.00;
        $rule = [
            'type' => 'invalid',
            'amount' => 5.00
        ];

        $this->expectException(\InvalidArgumentException::class);
        $service->applyRule($basePrice, $rule);
    }

    public function testNegativePriceResult(): void
    {
        $service = new PricingRulesService();

        $basePrice = 5.00;
        $rule = [
            'type' => 'fixed',
            'amount' => -10.00
        ];

        $result = $service->applyRule($basePrice, $rule);

        // Should allow negative prices (though business logic might prevent this)
        $this->assertEquals(-5.00, $result);
    }
}