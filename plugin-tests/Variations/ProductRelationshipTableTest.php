<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Variations\UI\ProductRelationshipTable;
use PHPUnit\Framework\TestCase;

class ProductRelationshipTableTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $coreDao;

    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    protected function setUp(): void
    {
        $this->coreDao       = $this->createMock(ProductAttributesDao::class);
        $this->variationsDao = $this->getMockBuilder(VariationsDao::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConstructor(): void
    {
        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);
        $this->assertInstanceOf(ProductRelationshipTable::class, $table);
    }

    public function testRenderWithNoProducts(): void
    {
        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([]);

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('Product Relationships', $output);
        $this->assertStringContainsString('No products found', $output);
    }

    public function testRenderShowsAllColumnsForSimpleProduct(): void
    {
        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([
                ['stock_id' => 'SIMPLE001', 'description' => 'A Simple Product', 'inactive' => 0],
            ]);

        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('SIMPLE001')
            ->willReturn([]);

        $this->variationsDao->expects($this->once())
            ->method('getProductParent')
            ->with('SIMPLE001')
            ->willReturn(null);

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('SIMPLE001', $output);
        $this->assertStringContainsString('A Simple Product', $output);
        $this->assertStringContainsString('Simple', $output);
        $this->assertStringContainsString('Stock ID', $output);
        $this->assertStringContainsString('Description', $output);
        $this->assertStringContainsString('Type', $output);
        $this->assertStringContainsString('Parent Stock ID', $output);
        $this->assertStringContainsString('Status', $output);
    }

    public function testRenderIdentifiesParentProduct(): void
    {
        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([
                ['stock_id' => 'PARENT001', 'description' => 'A Parent Product', 'inactive' => 0],
            ]);

        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('PARENT001')
            ->willReturn([
                ['stock_id' => 'PARENT001-S-RED', 'description' => 'A Parent Product (Variation)'],
            ]);

        $this->variationsDao->expects($this->never())
            ->method('getProductParent');

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('PARENT001', $output);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringContainsString('View Variations', $output);
    }

    public function testRenderIdentifiesVariationProduct(): void
    {
        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([
                ['stock_id' => 'PARENT001-S-RED', 'description' => 'A Variation', 'inactive' => 0],
            ]);

        $this->variationsDao->expects($this->once())
            ->method('getProductVariations')
            ->with('PARENT001-S-RED')
            ->willReturn([]);

        $this->variationsDao->expects($this->once())
            ->method('getProductParent')
            ->with('PARENT001-S-RED')
            ->willReturn(['stock_id' => 'PARENT001', 'description' => 'A Parent Product']);

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('PARENT001-S-RED', $output);
        $this->assertStringContainsString('Variation', $output);
        $this->assertStringContainsString('PARENT001', $output);
        $this->assertStringContainsString('View Parent', $output);
    }

    public function testRenderFilterParentsOnly(): void
    {
        $_GET['relationship_filter'] = 'parents';

        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([
                ['stock_id' => 'PARENT001', 'description' => 'Parent', 'inactive' => 0],
                ['stock_id' => 'SIMPLE001', 'description' => 'Simple', 'inactive' => 0],
            ]);

        $this->variationsDao->method('getProductVariations')
            ->willReturnMap([
                ['PARENT001', [['stock_id' => 'PARENT001-V1', 'description' => 'Variation 1']]],
                ['SIMPLE001', []],
            ]);

        $this->variationsDao->method('getProductParent')
            ->willReturn(null);

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        // Parent should be visible, Simple should not
        $this->assertStringContainsString('PARENT001', $output);
        $this->assertStringNotContainsString('SIMPLE001', $output);

        unset($_GET['relationship_filter']);
    }

    public function testRenderFilterVariationsOnly(): void
    {
        $_GET['relationship_filter'] = 'variations';

        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([
                ['stock_id' => 'PARENT001', 'description' => 'Parent', 'inactive' => 0],
                ['stock_id' => 'PARENT001-V1', 'description' => 'Variation', 'inactive' => 0],
            ]);

        $this->variationsDao->method('getProductVariations')
            ->willReturnMap([
                ['PARENT001', [['stock_id' => 'PARENT001-V1', 'description' => 'Variation']]],
                ['PARENT001-V1', []],
            ]);

        $this->variationsDao->method('getProductParent')
            ->willReturnMap([
                ['PARENT001-V1', ['stock_id' => 'PARENT001', 'description' => 'Parent']],
            ]);

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        // Variation should be visible, Parent row (type=Parent) should not
        $this->assertStringContainsString('PARENT001-V1', $output);
        // PARENT001 appears as a parent_stock_id reference, but not as a table-row stock_id with type Parent
        $this->assertStringNotContainsString('<td>PARENT001</td><td>Parent</td>', $output);

        unset($_GET['relationship_filter']);
    }

    public function testRenderShowsFilterBar(): void
    {
        $this->coreDao->expects($this->once())
            ->method('getAllProducts')
            ->willReturn([]);

        $table = new ProductRelationshipTable($this->coreDao, $this->variationsDao);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('relationship_filter=all', $output);
        $this->assertStringContainsString('relationship_filter=parents', $output);
        $this->assertStringContainsString('relationship_filter=variations', $output);
    }
}
