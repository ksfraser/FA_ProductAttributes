<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\DeleteTagAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use PHPUnit\Framework\TestCase;

class DeleteTagActionTest extends TestCase
{
    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var DeleteTagAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductTagsDao::class);
        $this->action = new DeleteTagAction($this->dao);
    }

    public function testHandleReturnErrorWhenTagIdMissing(): void
    {
        $this->dao->expects($this->never())->method('deleteTag');
        $result = $this->action->handle([]);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleReturnErrorWhenTagIdZero(): void
    {
        $this->dao->expects($this->never())->method('deleteTag');
        $result = $this->action->handle(['tag_id' => '0']);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleReturnErrorWhenTagIdNegative(): void
    {
        $this->dao->expects($this->never())->method('deleteTag');
        $result = $this->action->handle(['tag_id' => '-1']);
        $this->assertSame('Invalid tag ID', $result);
    }

    public function testHandleCallsDeleteTag(): void
    {
        $this->dao->expects($this->once())
            ->method('deleteTag')
            ->with(7);

        $result = $this->action->handle(['tag_id' => '7']);
        $this->assertSame('Tag deleted', $result);
    }

    public function testHandleCastsToInt(): void
    {
        $this->dao->expects($this->once())
            ->method('deleteTag')
            ->with(42);

        $this->action->handle(['tag_id' => 42]);
    }
}
