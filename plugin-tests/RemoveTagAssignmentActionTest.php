<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\RemoveTagAssignmentAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use PHPUnit\Framework\TestCase;

class RemoveTagAssignmentActionTest extends TestCase
{
    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var RemoveTagAssignmentAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductTagsDao::class);
        $this->action = new RemoveTagAssignmentAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('removeAssignment');
        $result = $this->action->handle([]);
        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenTagIdMissing(): void
    {
        $this->dao->expects($this->never())->method('removeAssignment');
        $result = $this->action->handle(['stock_id' => 'SKU001']);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleReturnErrorWhenTagIdZero(): void
    {
        $result = $this->action->handle(['stock_id' => 'SKU001', 'tag_id' => '0']);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleCallsRemoveAssignment(): void
    {
        $this->dao->expects($this->once())
            ->method('removeAssignment')
            ->with('SKU001', 3);

        $result = $this->action->handle(['stock_id' => 'SKU001', 'tag_id' => '3']);
        $this->assertSame('Tag removed', $result);
    }

    public function testHandleCastsToInt(): void
    {
        $this->dao->expects($this->once())
            ->method('removeAssignment')
            ->with('SKU001', 77);

        $this->action->handle(['stock_id' => 'SKU001', 'tag_id' => 77]);
    }

    public function testHandleTrimsStockId(): void
    {
        $this->dao->expects($this->once())
            ->method('removeAssignment')
            ->with('SKU001', 1);

        $this->action->handle(['stock_id' => '  SKU001  ', 'tag_id' => '1']);
    }
}
