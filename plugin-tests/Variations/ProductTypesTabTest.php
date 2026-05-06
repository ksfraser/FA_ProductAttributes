<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\UI;

use Ksfraser\FA_ProductAttributes\Variations\UI\ProductTypesTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class ProductTypesTabTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ProductTypesTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->getMockBuilder(ProductAttributesDao::class)->disableOriginalConstructor()->onlyMethods(['getAllProducts', 'listCategoryAssignments', 'getProductParent'])->getMock();
        $this->tab = new ProductTypesTab($this->dao);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(ProductTypesTab::class, $this->tab);
    }

    public function testRenderWithEmptyProducts(): void
    {
        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([]);

        $this->expectOutputRegex('/<form[^>]*>/');
        $this->expectOutputRegex('/Product Type Management/');
        $this->expectOutputRegex('/Stock ID/');
        $this->expectOutputRegex('/Description/');
        $this->expectOutputRegex('/Current Type/');
        $this->expectOutputRegex('/New Type/');
        $this->expectOutputRegex('/Parent Product/');

        $this->tab->render();
    }

    public function testRenderWithSimpleProducts(): void
    {
        $products = [
            [
                'stock_id' => 'TEST001',
                'description' => 'Test Product 1'
            ],
            [
                'stock_id' => 'TEST002',
                'description' => 'Test Product 2'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);

        $this->dao->expects($this->exactly(2))
            ->method('listCategoryAssignments')
            ->willReturn([]);

        $this->dao->expects($this->exactly(2))
            ->method('getProductParent')
            ->willReturn(null);

        $this->expectOutputRegex('/TEST001/');
        $this->expectOutputRegex('/TEST002/');
        $this->expectOutputRegex('/Test Product 1/');
        $this->expectOutputRegex('/Test Product 2/');
        $this->expectOutputRegex('/Simple/'); // Both should be Simple type

        $this->tab->render();
    }

    public function testRenderWithVariableProduct(): void
    {
        $products = [
            [
                'stock_id' => 'TEST001',
                'description' => 'Variable Product'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([['category_id' => 1]]); // Has category assignments = Variable

        $this->dao->expects($this->never())
            ->method('getProductParent');

        $this->expectOutputRegex('/Variable/');

        $this->tab->render();
    }

    public function testRenderWithVariationProduct(): void
    {
        $products = [
            [
                'stock_id' => 'TEST001',
                'description' => 'Variation Product'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([]); // No category assignments

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->willReturn('PARENT001'); // Has parent = Variation

        $this->expectOutputRegex('/Variation/');

        $this->tab->render();
    }

    public function testRenderIncludesFormElements(): void
    {
        $products = [
            [
                'stock_id' => 'TEST001',
                'description' => 'Test Product'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->willReturn(null);

        $this->expectOutputRegex('/select name="product_types\[TEST001\]"/');
        $this->expectOutputRegex('/option value="simple"/');
        $this->expectOutputRegex('/option value="variable"/');
        $this->expectOutputRegex('/option value="variation"/');
        $this->expectOutputRegex('/select name="parent_products\[TEST001\]"/');
        $this->expectOutputRegex('/input type="hidden" name="action" value="update_product_types"/');
        $this->expectOutputRegex('/input type="submit"/');

        $this->tab->render();
    }

    public function testRenderIncludesJavaScript(): void
    {
        $products = [
            [
                'stock_id' => 'TEST001',
                'description' => 'Test Product'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->willReturn(null);

        $this->expectOutputRegex('/<script>/');
        $this->expectOutputRegex('/DOMContentLoaded/');
        $this->expectOutputRegex('/product_types/');
        $this->expectOutputRegex('/parent_products/');
        $this->expectOutputRegex('/variation/');

        $this->tab->render();
    }

    public function testRenderWithMultipleProductsShowsParentOptions(): void
    {
        $products = [
            [
                'stock_id' => 'PARENT001',
                'description' => 'Parent Product'
            ],
            [
                'stock_id' => 'CHILD001',
                'description' => 'Child Product'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn($products);

        $this->dao->expects($this->exactly(2))
            ->method('listCategoryAssignments')
            ->willReturn([]);

        $this->dao->expects($this->exactly(2))
            ->method('getProductParent')
            ->willReturn(null);

        $this->expectOutputRegex('/PARENT001/');
        $this->expectOutputRegex('/CHILD001/');
        $this->expectOutputRegex('/option value="PARENT001"/'); // Should appear in parent select

        $this->tab->render();
    }

    public function testGetProductTypeReturnsSimpleForNoAssignmentsNoParent(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->willReturn(null);

        $reflection = new \ReflectionClass($this->tab);
        $method = $reflection->getMethod('getProductType');
        $method->setAccessible(true);

        $result = $method->invoke($this->tab, 'TEST001');

        $this->assertEquals('simple', $result);
    }

    public function testGetProductTypeReturnsVariableForCategoryAssignments(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([['category_id' => 1]]);

        $this->dao->expects($this->never())
            ->method('getProductParent');

        $reflection = new \ReflectionClass($this->tab);
        $method = $reflection->getMethod('getProductType');
        $method->setAccessible(true);

        $result = $method->invoke($this->tab, 'TEST001');

        $this->assertEquals('variable', $result);
    }

    public function testGetProductTypeReturnsVariationForParent(): void
    {
        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->willReturn('PARENT001');

        $reflection = new \ReflectionClass($this->tab);
        $method = $reflection->getMethod('getProductType');
        $method->setAccessible(true);

        $result = $method->invoke($this->tab, 'TEST001');

        $this->assertEquals('variation', $result);
    }

    public function testFormatProductType(): void
    {
        $reflection = new \ReflectionClass($this->tab);
        $method = $reflection->getMethod('formatProductType');
        $method->setAccessible(true);

        $this->assertEquals('Simple', $method->invoke($this->tab, 'simple'));
        $this->assertEquals('Variable', $method->invoke($this->tab, 'variable'));
        $this->assertEquals('Variation', $method->invoke($this->tab, 'variation'));
        $this->assertEquals('Unknown', $method->invoke($this->tab, 'invalid'));
    }
}