<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Service\FrontAccountingVariationService;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class FrontAccountingVariationServiceTest extends TestCase
{
    public function testCreateVariations(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $attributesDb = $this->createMock(DbAdapterInterface::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        // Mock the DAO to return assignments
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([
                [
                    'category_id' => 1,
                    'category_code' => 'size',
                    'category_label' => 'Size',
                    'category_sort_order' => 1,
                    'value_id' => 1,
                    'value_label' => 'Small',
                    'value_slug' => 's'
                ],
                [
                    'category_id' => 2,
                    'category_code' => 'color',
                    'category_label' => 'Color',
                    'category_sort_order' => 2,
                    'value_id' => 2,
                    'value_label' => 'Red',
                    'value_slug' => 'red'
                ]
            ]);

        // Mock FA DB calls
        $faDb->method('getTablePrefix')->willReturn('fa_');
        $faDb->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                [['description' => 'Test Product - ${SIZE} ${COLOR}']], // getParentDescription
                [[ // get parent data
                    'category_id' => 1,
                    'units' => 'ea',
                    'mb_flag' => 'B',
                    'sales_account' => '4000',
                    'cogs_account' => '5000',
                    'inventory_account' => '1000',
                    'adjustment_account' => '6000',
                    'wip_account' => '7000',
                    'dimension_id' => 0,
                    'dimension2_id' => 0,
                    'tax_type_id' => 1,
                    'sales_tax_included' => 0,
                    'base_sales_price' => 10.00,
                    'material_cost' => 5.00,
                    'labour_cost' => 0.00,
                    'overhead_cost' => 0.00,
                    'last_cost' => 5.00,
                    'actual_cost' => 5.00,
                    'no_sale' => 0,
                    'editable' => 1
                ]]
            );

        // Mock FA DB to expect variation creation
        $faDb->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO fa_stock_master'),
                $this->callback(function($params) {
                    return $params['stock_id'] === 'ABC123-S-RED' &&
                           $params['description'] === 'Test Product - Small Red' &&
                           $params['parent_stock_id'] === 'ABC123';
                })
            );

        $service = new FrontAccountingVariationService($dao, $attributesDb, $faDb);
        $created = $service->createVariations('ABC123', false);

        $this->assertEquals(['ABC123-S-RED'], $created);
    }

    public function testCreateVariationsWithPricing(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $attributesDb = $this->createMock(DbAdapterInterface::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        // Mock the DAO to return assignments
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('ABC123')
            ->willReturn([
                [
                    'category_id' => 1,
                    'category_code' => 'size',
                    'category_label' => 'Size',
                    'category_sort_order' => 1,
                    'value_id' => 1,
                    'value_label' => 'Small',
                    'value_slug' => 's'
                ]
            ]);

        // Mock FA DB
        $faDb->method('getTablePrefix')->willReturn('fa_');
        $faDb->expects($this->exactly(3))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                [['description' => 'Test Product - ${SIZE}']], // getParentDescription
                [[ // get parent data
                    'category_id' => 1,
                    'units' => 'ea',
                    'mb_flag' => 'B',
                    'sales_account' => '4000',
                    'cogs_account' => '5000',
                    'inventory_account' => '1000',
                    'adjustment_account' => '6000',
                    'wip_account' => '7000',
                    'dimension_id' => 0,
                    'dimension2_id' => 0,
                    'tax_type_id' => 1,
                    'sales_tax_included' => 0,
                    'base_sales_price' => 10.00,
                    'material_cost' => 5.00,
                    'labour_cost' => 0.00,
                    'overhead_cost' => 0.00,
                    'last_cost' => 5.00,
                    'actual_cost' => 5.00,
                    'no_sale' => 0,
                    'editable' => 1
                ]],
                [['stock_id' => 'ABC123', 'sales_type_id' => 1, 'curr_abrev' => 'USD', 'price' => 10.00]] // get parent prices
            );

        $faDb->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    $this->stringContains('INSERT INTO fa_stock_master'),
                    $this->callback(function($params) {
                        return $params['stock_id'] === 'ABC123-S';
                    })
                ],
                [
                    $this->stringContains('INSERT INTO `fa_prices`'),
                    $this->callback(function($params) {
                        return $params['stock_id'] === 'ABC123-S' &&
                               $params['price'] == 10.00;
                    })
                ]
            );

        $service = new FrontAccountingVariationService($dao, $attributesDb, $faDb);
        $created = $service->createVariations('ABC123', true);

        $this->assertEquals(['ABC123-S'], $created);
    }

    public function testGetParentDescription(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $attributesDb = $this->createMock(DbAdapterInterface::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $faDb->method('getTablePrefix')->willReturn('fa_');
        $faDb->expects($this->once())
            ->method('query')
            ->with('SELECT description FROM `fa_stock_master` WHERE stock_id = :stock_id', ['stock_id' => 'ABC123'])
            ->willReturn([['description' => 'Test Description']]);

        $service = new FrontAccountingVariationService($dao, $attributesDb, $faDb);
        $description = $this->invokePrivateMethod($service, 'getParentDescription', ['ABC123']);

        $this->assertEquals('Test Description', $description);
    }

    /**
     * Helper method to invoke private/protected methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}