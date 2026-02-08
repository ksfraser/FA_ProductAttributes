<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\RemoveCategoryAssignmentAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class RemoveCategoryAssignmentActionTest extends TestCase
{
    public function testHandleWithValidData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('removeCategoryAssignment')
            ->with('TEST123', 456);

        $action = new RemoveCategoryAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '456'
        ]);

        $this->assertEquals("Removed category assignment", $result);
    }

    public function testHandleWithEmptyStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $action = new RemoveCategoryAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => '',
            'category_id' => '456'
        ]);

        $this->assertEquals("Invalid category assignment data", $result);
    }

    public function testHandleWithWhitespaceStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $action = new RemoveCategoryAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => '   ',
            'category_id' => '456'
        ]);

        $this->assertEquals("Invalid category assignment data", $result);
    }

    public function testHandleWithInvalidCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $action = new RemoveCategoryAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '0'
        ]);

        $this->assertEquals("Invalid category assignment data", $result);
    }

    public function testHandleWithNegativeCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $action = new RemoveCategoryAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '-1'
        ]);

        $this->assertEquals("Invalid category assignment data", $result);
    }

    public function testHandleWithMissingData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $action = new RemoveCategoryAssignmentAction($dao);

        $result = $action->handle([]);

        $this->assertEquals("Invalid category assignment data", $result);
    }
}