<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\Actions;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Actions\UpdateProductTypesAction;

class UpdateProductTypesActionTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpdateProductTypesAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao = $this->getMockBuilder(ProductAttributesDao::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllProducts', 'listCategoryAssignments', 'getProductParent',
                           'removeCategoryAssignment', 'setProductParent'])
            ->getMock();
        $this->action = new UpdateProductTypesAction($this->dao);
    }

    public function testHandleWithValidData(): void
    {
        $postData = [
            'product_types' => [
                'PROD001' => 'variable',   // simple   -> variable
                'PROD002' => 'simple',     // variable -> simple
                'PROD003' => 'variation',  // simple   -> variation
            ],
            'parent_products' => [
                'PROD003' => 'PROD001'
            ]
        ];

        // listCategoryAssignments: PROD001(getCurrentType), PROD002(getCurrentType),
        //   PROD002(clearProductAssignments), PROD003(getCurrentType), PROD003(clearProductAssignments)
        $this->dao->expects($this->exactly(5))
            ->method('listCategoryAssignments')
            ->willReturnOnConsecutiveCalls(
                [],             // PROD001 getCurrentType: no categories
                [['id' => 1]], // PROD002 getCurrentType: has categories -> variable
                [['id' => 1]], // PROD002 clearProductAssignments
                [],             // PROD003 getCurrentType: no categories
                []              // PROD003 clearProductAssignments
            );

        // getProductParent: PROD001(getCurrentType), PROD003(getCurrentType), PROD003(isParentChanging)
        $this->dao->expects($this->exactly(3))
            ->method('getProductParent')
            ->willReturnOnConsecutiveCalls(null, null, null);

        // removeCategoryAssignment called once for PROD002's one category
        $this->dao->expects($this->once())
            ->method('removeCategoryAssignment')
            ->with('PROD002', 1);

        // setProductParent: PROD001->null, PROD002->null, PROD003->PROD001
        $this->dao->expects($this->exactly(3))
            ->method('setProductParent')
            ->withConsecutive(
                ['PROD001', null],
                ['PROD002', null],
                ['PROD003', 'PROD001']
            );

        $result = $this->action->handle($postData);
        $this->assertStringContainsString('Updated product types for 3 products', $result);
    }

    public function testHandleWithNoChanges(): void
    {
        $postData = [
            'product_types' => [
                'PROD001' => 'simple',
                'PROD002' => 'variable'
            ]
        ];

        $this->dao->expects($this->exactly(2))
            ->method('listCategoryAssignments')
            ->willReturnOnConsecutiveCalls(
                [],             // PROD001: no categories
                [['id' => 1]]  // PROD002: has categories -> variable
            );
        $this->dao->expects($this->once())
            ->method('getProductParent')
            ->with('PROD001')
            ->willReturn(null);

        $result = $this->action->handle($postData);
        $this->assertStringContainsString('Updated product types for 0 products', $result);
    }

    public function testHandleWithEmptyData(): void
    {
        $result = $this->action->handle([]);
        $this->assertStringContainsString('Updated product types for 0 products', $result);
    }

    public function testHandleVariationWithoutParent(): void
    {
        $postData = [
            'product_types' => ['PROD001' => 'variation'],
            'parent_products' => ['PROD001' => '']
        ];

        $this->dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('PROD001')
            ->willReturn([]);

        $this->dao->expects($this->exactly(2))
            ->method('getProductParent')
            ->with('PROD001')
            ->willReturn(null);

        $this->dao->expects($this->never())->method('setProductParent');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parent product is required for variation type');

        $this->action->handle($postData);
    }

    public function testHandleConvertingToVariationWithExistingParent(): void
    {
        $postData = [
            'product_types' => ['PROD001' => 'variation'],
            'parent_products' => ['PROD001' => 'PARENT001']
        ];

        // listCategoryAssignments: getCurrentType + clearProductAssignments
        $this->dao->expects($this->exactly(2))
            ->method('listCategoryAssignments')
            ->with('PROD001')
            ->willReturn([]);

        // getProductParent: getCurrentType + isParentChanging (both return old parent)
        $this->dao->expects($this->exactly(2))
            ->method('getProductParent')
            ->with('PROD001')
            ->willReturn('OLD_PARENT');

        $this->dao->expects($this->once())
            ->method('setProductParent')
            ->with('PROD001', 'PARENT001');

        $result = $this->action->handle($postData);
        $this->assertStringContainsString('Updated product types for 1 products', $result);
    }
}