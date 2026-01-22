<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Actions\UpdateProductTypesAction;

class UpdateProductTypesActionTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpdateProductTypesAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductAttributesDao::class);
        $this->action = new UpdateProductTypesAction($this->dao);
    }

    public function testHandleWithValidData()
    {
        $postData = [
            'product_types' => [
                'PROD001' => 'variable',
                'PROD002' => 'simple',
                'PROD003' => 'variation'
            ],
            'parent_products' => [
                'PROD003' => 'PROD001'
            ]
        ];

        // Mock current product types
        $this->dao->expects($this->exactly(3))
            ->method('listCategoryAssignments')
            ->willReturnOnConsecutiveCalls(
                [], // PROD001: no categories (simple -> variable)
                [['id' => 1]], // PROD002: has categories (variable -> simple)
                [] // PROD003: no categories (simple -> variation)
            );

        // Mock parent product check for PROD003
        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->with('PROD003')
            ->willReturn(null);

        // Expect relationship management calls
        $this->dao->expects($this->once())
            ->method('clearParentRelationship')
            ->with('PROD002'); // Converting from variable to simple

        $this->dao->expects($this->once())
            ->method('setParentRelationship')
            ->with('PROD003', 'PROD001'); // Setting parent for variation

        $result = $this->action->handle($postData);

        $this->assertStringContains('Updated product types for 3 products', $result);
    }

    public function testHandleWithNoChanges()
    {
        $postData = [
            'product_types' => [
                'PROD001' => 'simple',
                'PROD002' => 'variable'
            ]
        ];

        // Mock current product types matching the requested types
        $this->dao->expects($this->exactly(2))
            ->method('listCategoryAssignments')
            ->willReturnOnConsecutiveCalls(
                [], // PROD001: simple (no change)
                [['id' => 1]] // PROD002: variable (no change)
            );

        $result = $this->action->handle($postData);

        $this->assertStringContains('Updated product types for 0 products', $result);
    }

    public function testHandleWithEmptyData()
    {
        $postData = [];

        $result = $this->action->handle($postData);

        $this->assertStringContains('Updated product types for 0 products', $result);
    }

    public function testHandleVariationWithoutParent()
    {
        $postData = [
            'product_types' => [
                'PROD001' => 'variation'
            ],
            'parent_products' => [
                'PROD001' => '' // Empty parent
            ]
        ];

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('PROD001')
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->with('PROD001')
            ->willReturn(null);

        // Should not call setParentRelationship with empty parent
        $this->dao->expects($this->never())
            ->method('setParentRelationship');

        $result = $this->action->handle($postData);

        $this->assertStringContains('Updated product types for 1 products', $result);
    }

    public function testHandleConvertingToVariationWithExistingParent()
    {
        $postData = [
            'product_types' => [
                'PROD001' => 'variation'
            ],
            'parent_products' => [
                'PROD001' => 'PARENT001'
            ]
        ];

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('PROD001')
            ->willReturn([]);

        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->with('PROD001')
            ->willReturn(['stock_id' => 'OLD_PARENT', 'description' => 'Old Parent']);

        // Should clear old relationship and set new one
        $this->dao->expects($this->once())
            ->method('clearParentRelationship')
            ->with('PROD001');

        $this->dao->expects($this->once())
            ->method('setParentRelationship')
            ->with('PROD001', 'PARENT001');

        $result = $this->action->handle($postData);

        $this->assertStringContains('Updated product types for 1 products', $result);
    }
}