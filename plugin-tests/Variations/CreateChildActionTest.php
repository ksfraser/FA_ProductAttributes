<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\Actions;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Variations\Actions\CreateChildAction;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class CreateChildActionTest extends TestCase
{
    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $coreDao;

    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var CreateChildAction */
    private $action;

    protected function setUp(): void
    {
        $this->variationsDao = $this->getMockBuilder(VariationsDao::class)->disableOriginalConstructor()->getMock();
        $this->coreDao       = $this->getMockBuilder(ProductAttributesDao::class)->disableOriginalConstructor()->getMock();
        $this->db            = $this->createMock(DbAdapterInterface::class);
        $this->action        = new CreateChildAction($this->variationsDao, $this->coreDao, $this->db);
    }

    public function testHandleCreatesAllCombinations(): void
    {
        $stockId = 'PARENT001';
        $postData = ['stock_id' => $stockId];

        $parentData = [
            'stock_id' => $stockId,
            'description' => 'Test Product',
            'mb_flag' => 'B',
        ];

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with($stockId)
            ->willReturn($parentData);

        $this->variationsDao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with($stockId)
            ->willReturn([
                ['id' => 1, 'label' => 'Color'],
                ['id' => 2, 'label' => 'Size'],
            ]);

        $this->variationsDao->method('listActiveValues')
            ->willReturnMap([
                [1, [['id' => 10, 'slug' => 'red', 'value' => 'Red'], ['id' => 11, 'slug' => 'blue', 'value' => 'Blue']]],
                [2, [['id' => 20, 'slug' => 'sm', 'value' => 'Small'], ['id' => 21, 'slug' => 'lg', 'value' => 'Large']]],
            ]);

        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->db->method('query')->willReturn([]);

        $this->variationsDao->expects($this->exactly(4))
            ->method('createChildProduct');

        $this->variationsDao->expects($this->exactly(4))
            ->method('copyParentCategoryAssignments');

        $this->variationsDao->expects($this->exactly(4))
            ->method('setParentRelationship');

        $this->coreDao->expects($this->exactly(8))
            ->method('addAssignment');

        $result = $this->action->handle($postData);

        $this->assertStringContainsString('4', $result);
    }

    public function testHandleSkipsExistingCombinations(): void
    {
        $stockId = 'PARENT001';
        $postData = ['stock_id' => $stockId];

        $parentData = [
            'stock_id' => $stockId,
            'description' => 'Test Product',
            'mb_flag' => 'B',
        ];

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with($stockId)
            ->willReturn($parentData);

        $this->variationsDao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with($stockId)
            ->willReturn([
                ['id' => 1, 'label' => 'Color'],
            ]);

        $this->variationsDao->method('listActiveValues')
            ->willReturnMap([
                [1, [['id' => 10, 'slug' => 'red', 'value' => 'Red'], ['id' => 11, 'slug' => 'blue', 'value' => 'Blue']]],
            ]);

        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->db->method('query')->willReturn(['existing']);

        $this->variationsDao->expects($this->never())
            ->method('createChildProduct');

        $result = $this->action->handle($postData);

        $this->assertStringContainsString('0', $result);
    }

    public function testHandleWithEmptyStockId(): void
    {
        $postData = ['stock_id' => ''];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock ID is required');

        $this->action->handle($postData);
    }

    public function testHandleWithMissingStockId(): void
    {
        $postData = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock ID is required');

        $this->action->handle($postData);
    }

    public function testHandleWithNonexistentParent(): void
    {
        $stockId = 'NONEXISTENT';
        $postData = ['stock_id' => $stockId];

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with($stockId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Parent product '$stockId' not found");

        $this->action->handle($postData);
    }

    public function testHandleWithNoCategoriesAssigned(): void
    {
        $stockId = 'PARENT001';
        $postData = ['stock_id' => $stockId];

        $parentData = [
            'stock_id' => $stockId,
            'description' => 'Test Product',
            'mb_flag' => 'B',
        ];

        $this->variationsDao->expects($this->once())
            ->method('getParentProductData')
            ->with($stockId)
            ->willReturn($parentData);

        $this->variationsDao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with($stockId)
            ->willReturn([]);

        $result = $this->action->handle($postData);

        $this->assertStringContainsString('No categories assigned', $result);
    }
}
