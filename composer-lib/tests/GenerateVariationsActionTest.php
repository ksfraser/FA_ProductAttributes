<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\GenerateVariationsAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class GenerateVariationsActionTest extends TestCase
{
    public function testHandleWithInvalidStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        $result = $action->handle([]);

        $this->assertEquals("Invalid stock ID", $result);
    }

    public function testHandleWithEmptyStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        $result = $action->handle(['stock_id' => '']);

        $this->assertEquals("Invalid stock ID", $result);
    }

    public function testHandleWithNoAssignedCategories(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        $result = $action->handle(['stock_id' => 'TEST123']);

        $this->assertEquals("No categories assigned to this product", $result);
    }

    public function testHandleWithCategoriesButNoValues(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 1, 'code' => 'color', 'label' => 'Color']
            ]);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([]);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        $result = $action->handle(['stock_id' => 'TEST123']);

        $this->assertEquals("No values found for assigned categories", $result);
    }

    public function testGenerateCombinationsWithSingleCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($action);
        $method = $reflection->getMethod('generateCombinations');
        $method->setAccessible(true);

        $categoryValues = [
            1 => [
                ['id' => 1, 'value' => 'Red', 'slug' => 'red'],
                ['id' => 2, 'value' => 'Blue', 'slug' => 'blue']
            ]
        ];

        $result = $method->invoke($action, $categoryValues);

        $this->assertCount(2, $result);
        // For single category, each combination should be an array with 1 item
        $this->assertCount(1, $result[0]);
        $this->assertCount(1, $result[1]);
        
        $this->assertEquals([
            'category_id' => 1,
            'value_id' => 1,
            'value_slug' => 'red',
            'value_label' => 'Red'
        ], $result[0][0]);
        $this->assertEquals([
            'category_id' => 1,
            'value_id' => 2,
            'value_slug' => 'blue',
            'value_label' => 'Blue'
        ], $result[1][0]);
    }

    public function testGenerateCombinationsWithTwoCategories(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($action);
        $method = $reflection->getMethod('generateCombinations');
        $method->setAccessible(true);

        $categoryValues = [
            1 => [
                ['id' => 1, 'value' => 'Red', 'slug' => 'red'],
                ['id' => 2, 'value' => 'Blue', 'slug' => 'blue']
            ],
            2 => [
                ['id' => 3, 'value' => 'Small', 'slug' => 'small'],
                ['id' => 4, 'value' => 'Large', 'slug' => 'large']
            ]
        ];

        $result = $method->invoke($action, $categoryValues);

        $this->assertCount(4, $result);
        
        // Each combination should be an array with 2 items (one from each category)
        foreach ($result as $combination) {
            $this->assertCount(2, $combination);
            foreach ($combination as $valueData) {
                $this->assertArrayHasKey('category_id', $valueData);
                $this->assertArrayHasKey('value_id', $valueData);
                $this->assertArrayHasKey('value_slug', $valueData);
                $this->assertArrayHasKey('value_label', $valueData);
            }
        }

        // Check that we have the expected combinations
        $allSlugCombos = [];
        foreach ($result as $combination) {
            $slugs = array_column($combination, 'value_slug');
            $allSlugCombos[] = implode('-', $slugs);
        }

        // Should have: red-small, red-large, blue-small, blue-large
        $expectedCombos = ['red-small', 'red-large', 'blue-small', 'blue-large'];
        foreach ($expectedCombos as $expected) {
            $this->assertContains($expected, $allSlugCombos);
        }
    }

    public function testGenerateVariationStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'color', 'sort_order' => 2],
                ['id' => 2, 'code' => 'size', 'sort_order' => 1]
            ]);

        $dbAdapter = $this->createMock(DbAdapterInterface::class);

        $action = new GenerateVariationsAction($dao, $dbAdapter);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($action);
        $method = $reflection->getMethod('generateVariationStockId');
        $method->setAccessible(true);

        $combination = [
            ['category_id' => 1, 'value_slug' => 'red'],
            ['category_id' => 2, 'value_slug' => 'large']
        ];

        $result = $method->invoke($action, 'TEST123', $combination);

        // Should be sorted by royal order (size first, then color)
        $this->assertEquals('TEST123-large-red', $result);
    }
}