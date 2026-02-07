<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\AddAssignmentAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class AddAssignmentActionTest extends TestCase
{
    public function testHandleWithValidData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('addAssignment')
            ->with('TEST123', 1, 2, 5);

        $action = new AddAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '1',
            'value_id' => '2',
            'sort_order' => '5'
        ]);

        $this->assertEquals("Added assignment", $result);
    }

    public function testHandleWithMissingStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('addAssignment');

        $action = new AddAssignmentAction($dao);

        $result = $action->handle([
            'category_id' => '1',
            'value_id' => '2'
        ]);

        $this->assertEquals("Invalid assignment data", $result);
    }

    public function testHandleWithEmptyStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('addAssignment');

        $action = new AddAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => '',
            'category_id' => '1',
            'value_id' => '2'
        ]);

        $this->assertEquals("Invalid assignment data", $result);
    }

    public function testHandleWithInvalidCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('addAssignment');

        $action = new AddAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '0',
            'value_id' => '2'
        ]);

        $this->assertEquals("Invalid assignment data", $result);
    }

    public function testHandleWithInvalidValueId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('addAssignment');

        $action = new AddAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '1',
            'value_id' => '0'
        ]);

        $this->assertEquals("Invalid assignment data", $result);
    }

    public function testHandleWithDefaultSortOrder(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('addAssignment')
            ->with('TEST123', 1, 2, 0);

        $action = new AddAssignmentAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST123',
            'category_id' => '1',
            'value_id' => '2'
        ]);

        $this->assertEquals("Added assignment", $result);
    }
}