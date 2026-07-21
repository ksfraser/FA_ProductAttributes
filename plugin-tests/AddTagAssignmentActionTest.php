<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\AddTagAssignmentAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use PHPUnit\Framework\TestCase;

class AddTagAssignmentActionTest extends TestCase
{
    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var AddTagAssignmentAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductTagsDao::class);
        $this->action = new AddTagAssignmentAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('addAssignment');
        $result = $this->action->handle([]);
        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenTagIdMissing(): void
    {
        $this->dao->expects($this->never())->method('addAssignment');
        $result = $this->action->handle(['stock_id' => 'SKU001']);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleReturnErrorWhenTagIdZero(): void
    {
        $this->dao->expects($this->never())->method('addAssignment');
        $result = $this->action->handle(['stock_id' => 'SKU001', 'tag_id' => '0']);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleCallsAddAssignment(): void
    {
        $this->dao->expects($this->once())
            ->method('addAssignment')
            ->with('SKU001', 5);

        $result = $this->action->handle(['stock_id' => 'SKU001', 'tag_id' => '5']);
        $this->assertSame('Tag assigned', $result);
    }

    public function testHandleCastsToInt(): void
    {
        $this->dao->expects($this->once())
            ->method('addAssignment')
            ->with('SKU001', 99);

        $this->action->handle(['stock_id' => 'SKU001', 'tag_id' => 99]);
    }

    public function testHandleTrimsStockId(): void
    {
        $this->dao->expects($this->once())
            ->method('addAssignment')
            ->with('SKU001', 1);

        $this->action->handle(['stock_id' => '  SKU001  ', 'tag_id' => '1']);
    }
}
