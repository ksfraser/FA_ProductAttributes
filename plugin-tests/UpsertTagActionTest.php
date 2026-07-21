<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertTagAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use PHPUnit\Framework\TestCase;

class UpsertTagActionTest extends TestCase
{
    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpsertTagAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductTagsDao::class);
        $this->action = new UpsertTagAction($this->dao);
    }

    public function testHandleReturnErrorWhenNameMissing(): void
    {
        $this->dao->expects($this->never())->method('upsertTag');
        $result = $this->action->handle([]);
        $this->assertSame('Tag name is required', $result);
    }

    public function testHandleReturnErrorWhenNameBlank(): void
    {
        $this->dao->expects($this->never())->method('upsertTag');
        $result = $this->action->handle(['name' => '   ']);
        $this->assertSame('Tag name is required', $result);
    }

    public function testHandleAutoGeneratesSlugFromName(): void
    {
        $this->dao->expects($this->once())
            ->method('upsertTag')
            ->with('Sale Items', 'sale-items', 0);

        $this->action->handle(['name' => 'Sale Items']);
    }

    public function testHandleSanitisesSlug(): void
    {
        $this->dao->expects($this->once())
            ->method('upsertTag')
            ->with('New Arrivals!!', 'new-arrivals', 0);

        $this->action->handle(['name' => 'New Arrivals!!']);
    }

    public function testHandleReturnsInvalidSlugIfEmptyAfterSanitisation(): void
    {
        $this->dao->expects($this->never())->method('upsertTag');
        $result = $this->action->handle(['name' => '!!!']);
        $this->assertSame('Invalid tag slug', $result);
    }

    public function testHandleReturnsInvalidSlugIfOnlyPunctuation(): void
    {
        $this->dao->expects($this->never())->method('upsertTag');
        $result = $this->action->handle(['name' => '@#$%^&']);
        $this->assertSame('Invalid tag slug', $result);
    }

    public function testHandleUsesProvidedSlug(): void
    {
        $this->dao->expects($this->once())
            ->method('upsertTag')
            ->with('Sale', 'clearance-items', 0);

        $this->action->handle(['name' => 'Sale', 'slug' => 'clearance-items']);
    }

    public function testHandleSanitisesProvidedSlug(): void
    {
        $this->dao->expects($this->once())
            ->method('upsertTag')
            ->with('Sale', 'saleitems', 0);

        $this->action->handle(['name' => 'Sale', 'slug' => 'Sale Items!!']);
    }

    public function testHandleCreateReturnsCreatedMessage(): void
    {
        $this->dao->expects($this->once())->method('upsertTag');

        $result = $this->action->handle(['name' => 'New Tag']);
        $this->assertSame('Tag created', $result);
    }

    public function testHandleUpdateReturnsUpdatedMessage(): void
    {
        $this->dao->expects($this->once())->method('upsertTag');

        $result = $this->action->handle(['name' => 'Existing Tag', 'tag_id' => '5']);
        $this->assertSame('Tag updated', $result);
    }
}
