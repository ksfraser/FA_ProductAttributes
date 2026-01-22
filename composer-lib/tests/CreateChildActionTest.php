<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildAction;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class CreateChildActionTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var CreateChildAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->action = new CreateChildAction($this->dao);
    }

    public function testHandleWithValidStockId()
    {
        $stockId = 'TEST001';
        $postData = ['stock_id' => $stockId];

        // Mock parent product data
        $parentData = [
            'stock_id' => $stockId,
            'description' => 'Test Product',
            'long_description' => 'Test Description',
            'mb_flag' => 'B'
        ];

        $this->dao->expects($this->once())
            ->method('getParentProductData')
            ->with($stockId)
            ->willReturn($parentData);

        $this->dao->expects($this->once())
            ->method('createChildProduct')
            ->with($this->callback(function($childStockId) use ($stockId) {
                return strpos($childStockId, $stockId . '-VAR-') === 0;
            }), $parentData);

        $this->dao->expects($this->once())
            ->method('copyParentCategoryAssignments')
            ->with($this->callback(function($childStockId) use ($stockId) {
                return strpos($childStockId, $stockId . '-VAR-') === 0;
            }), $stockId);

        $this->dao->expects($this->once())
            ->method('setParentRelationship')
            ->with($this->callback(function($childStockId) use ($stockId) {
                return strpos($childStockId, $stockId . '-VAR-') === 0;
            }), $stockId);

        $result = $this->action->handle($postData);

        $this->assertStringContains('Child product', $result);
        $this->assertStringContains('created successfully', $result);
    }

    public function testHandleWithEmptyStockId()
    {
        $postData = ['stock_id' => ''];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock ID is required');

        $this->action->handle($postData);
    }

    public function testHandleWithMissingStockId()
    {
        $postData = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock ID is required');

        $this->action->handle($postData);
    }

    public function testHandleWithNonexistentParent()
    {
        $stockId = 'NONEXISTENT';
        $postData = ['stock_id' => $stockId];

        $this->dao->expects($this->once())
            ->method('getParentProductData')
            ->with($stockId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Parent product '$stockId' not found");

        $this->action->handle($postData);
    }
}