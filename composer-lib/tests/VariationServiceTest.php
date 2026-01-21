<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Service\VariationService;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class VariationServiceTest extends TestCase
{
    public function testGenerateCombinationsEmpty(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);
        
        $service = new VariationService($dao, $db);
        
        $combinations = $this->invokePrivateMethod($service, 'generateCombinations', [[]]);
        
        $this->assertEquals([[]], $combinations);
    }

    public function testGenerateCombinationsSingleCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);
        
        $service = new VariationService($dao, $db);
        
        $categories = [
            [
                'id' => 1,
                'code' => 'color',
                'label' => 'Color',
                'values' => [
                    ['id' => 1, 'value' => 'Red', 'slug' => 'red'],
                    ['id' => 2, 'value' => 'Blue', 'slug' => 'blue']
                ]
            ]
        ];
        
        $combinations = $this->invokePrivateMethod($service, 'generateCombinations', [$categories]);
        
        $expected = [
            [['id' => 1, 'value' => 'Red', 'slug' => 'red']],
            [['id' => 2, 'value' => 'Blue', 'slug' => 'blue']]
        ];
        
        $this->assertEquals($expected, $combinations);
    }

    public function testGenerateCombinationsTwoCategories(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);
        
        $service = new VariationService($dao, $db);
        
        $categories = [
            [
                'id' => 1,
                'code' => 'size',
                'label' => 'Size',
                'values' => [
                    ['id' => 1, 'value' => 'Small', 'slug' => 's'],
                    ['id' => 2, 'value' => 'Large', 'slug' => 'l']
                ]
            ],
            [
                'id' => 2,
                'code' => 'color',
                'label' => 'Color',
                'values' => [
                    ['id' => 3, 'value' => 'Red', 'slug' => 'red'],
                    ['id' => 4, 'value' => 'Blue', 'slug' => 'blue']
                ]
            ]
        ];
        
        $combinations = $this->invokePrivateMethod($service, 'generateCombinations', [$categories]);
        
        $expected = [
            [
                ['id' => 1, 'value' => 'Small', 'slug' => 's'],
                ['id' => 3, 'value' => 'Red', 'slug' => 'red']
            ],
            [
                ['id' => 1, 'value' => 'Small', 'slug' => 's'],
                ['id' => 4, 'value' => 'Blue', 'slug' => 'blue']
            ],
            [
                ['id' => 2, 'value' => 'Large', 'slug' => 'l'],
                ['id' => 3, 'value' => 'Red', 'slug' => 'red']
            ],
            [
                ['id' => 2, 'value' => 'Large', 'slug' => 'l'],
                ['id' => 4, 'value' => 'Blue', 'slug' => 'blue']
            ]
        ];
        
        $this->assertEquals($expected, $combinations);
    }

    public function testGenerateVariationStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);
        
        $service = new VariationService($dao, $db);
        
        $combination = [
            ['slug' => 's', 'category_code' => 'size'],
            ['slug' => 'red', 'category_code' => 'color']
        ];
        
        $stockId = $this->invokePrivateMethod($service, 'generateVariationStockId', ['ABC123', $combination]);
        
        $this->assertEquals('ABC123-S-RED', $stockId);
    }

    public function testGenerateVariationDescription(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        // Mock the database query for parent description
        $db->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT description FROM `fa_stock_master` WHERE stock_id = :stock_id', ['stock_id' => 'ABC123'])
            ->willReturn([['description' => 'Product ABC123 - ${SIZE} ${COLOR}']]);

        $service = new VariationService($dao, $db);
        
        $combination = [
            ['value' => 'Small', 'category_code' => 'size'],
            ['value' => 'Red', 'category_code' => 'color']
        ];
        
        $description = $this->invokePrivateMethod($service, 'generateVariationDescription', ['ABC123', $combination]);
        
        $this->assertEquals('Product ABC123 - Small Red', $description);
    }

    public function testGenerateVariations(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);
        
        // Mock the database query for parent description
        $db->expects($this->exactly(2))
            ->method('getTablePrefix')
            ->willReturn('fa_');
        $db->expects($this->exactly(2))
            ->method('query')
            ->with('SELECT description FROM `fa_stock_master` WHERE stock_id = :stock_id', ['stock_id' => 'ABC123'])
            ->willReturn([['description' => 'Product ABC123 - ${SIZE} ${COLOR}']]);
        
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
                    'category_id' => 1,
                    'category_code' => 'size',
                    'category_label' => 'Size',
                    'category_sort_order' => 1,
                    'value_id' => 2,
                    'value_label' => 'Large',
                    'value_slug' => 'l'
                ],
                [
                    'category_id' => 2,
                    'category_code' => 'color',
                    'category_label' => 'Color',
                    'category_sort_order' => 2,
                    'value_id' => 3,
                    'value_label' => 'Red',
                    'value_slug' => 'red'
                ]
            ]);
        
        $service = new VariationService($dao, $db);
        $variations = $service->generateVariations('ABC123');
        
        $this->assertCount(2, $variations);
        $this->assertEquals('ABC123-S-RED', $variations[0]['stock_id']);
        $this->assertEquals('ABC123-L-RED', $variations[1]['stock_id']);
        $this->assertEquals('Product ABC123 - Small Red', $variations[0]['description']);
        $this->assertEquals('Product ABC123 - Large Red', $variations[1]['description']);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}