<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\DeleteAssignmentAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class DeleteAssignmentActionTest extends TestCase
{
    public function testHandleWithValidAssignmentId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(123);

        $action = new DeleteAssignmentAction($dao);

        $result = $action->handle([
            'assignment_id' => '123',
            'stock_id' => 'TEST123'
        ]);

        $this->assertEquals("Assignment removed successfully", $result);
    }

    public function testHandleWithInvalidAssignmentId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('deleteAssignment');

        $action = new DeleteAssignmentAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Assignment ID is required");

        $action->handle([
            'assignment_id' => '0',
            'stock_id' => 'TEST123'
        ]);
    }

    public function testHandleWithNegativeAssignmentId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('deleteAssignment');

        $action = new DeleteAssignmentAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Assignment ID is required");

        $action->handle([
            'assignment_id' => '-1',
            'stock_id' => 'TEST123'
        ]);
    }

    public function testHandleWithMissingAssignmentId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('deleteAssignment');

        $action = new DeleteAssignmentAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Assignment ID is required");

        $action->handle([
            'stock_id' => 'TEST123'
        ]);
    }
}