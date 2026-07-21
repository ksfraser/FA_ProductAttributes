<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\DeleteProductMediaAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use PHPUnit\Framework\TestCase;

class DeleteProductMediaActionTest extends TestCase
{
    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var DeleteProductMediaAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductMediaDao::class);
        $this->action = new DeleteProductMediaAction($this->dao);
    }

    public function testHandleReturnErrorWhenMediaIdMissing(): void
    {
        $this->dao->expects($this->never())->method('deleteMedia');
        $result = $this->action->handle([]);
        $this->assertSame('Invalid media ID', $result);
    }

    public function testHandleReturnErrorWhenMediaIdZero(): void
    {
        $this->dao->expects($this->never())->method('deleteMedia');
        $result = $this->action->handle(['media_id' => '0']);
        $this->assertSame('Invalid media ID', $result);
    }

    public function testHandleReturnErrorWhenMediaIdNegative(): void
    {
        $this->dao->expects($this->never())->method('deleteMedia');
        $result = $this->action->handle(['media_id' => '-5']);
        $this->assertSame('Invalid media ID', $result);
    }

    public function testHandleCallsDeleteMedia(): void
    {
        $this->dao->expects($this->once())
            ->method('deleteMedia')
            ->with(42);

        $result = $this->action->handle(['media_id' => '42']);
        $this->assertSame('Media deleted', $result);
    }

    public function testHandleCastsToInt(): void
    {
        $this->dao->expects($this->once())
            ->method('deleteMedia')
            ->with(99);

        $this->action->handle(['media_id' => 99]);
    }
}
