<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertTagAction;
use Ksfraser\FA_ProductAttributes\Actions\DeleteTagAction;
use Ksfraser\FA_ProductAttributes\Actions\AddTagAssignmentAction;
use Ksfraser\FA_ProductAttributes\Actions\RemoveTagAssignmentAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use PHPUnit\Framework\TestCase;

class TagActionsTest extends TestCase
{
    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductTagsDao::class);
    }

    // ── UpsertTagAction ───────────────────────────────────────────────────────

    public function testUpsertTagReturnErrorWhenNameMissing(): void
    {
        $action = new UpsertTagAction($this->dao);
        $this->dao->expects($this->never())->method('upsertTag');

        $result = $action->handle([]);

        $this->assertSame('Tag name is required', $result);
    }

    public function testUpsertTagAutoGeneratesSlugFromName(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsertTag')
            ->with('On Sale', $this->callback(function ($slug) use (&$captured) {
                $captured = $slug;
                return true;
            }), 0);

        $action = new UpsertTagAction($this->dao);
        $action->handle(['name' => 'On Sale', 'tag_id' => '0']);

        $this->assertSame('on-sale', $captured);
    }

    public function testUpsertTagUsesProvidedSlug(): void
    {
        $this->dao->expects($this->once())
            ->method('upsertTag')
            ->with('On Sale', 'custom-slug', 0);

        $action = new UpsertTagAction($this->dao);
        $action->handle(['name' => 'On Sale', 'slug' => 'custom-slug', 'tag_id' => '0']);
    }

    public function testUpsertTagReturnsCreatedMessageForNewTag(): void
    {
        $action = new UpsertTagAction($this->dao);

        $result = $action->handle(['name' => 'Sale', 'tag_id' => '0']);

        $this->assertSame('Tag created', $result);
    }

    public function testUpsertTagReturnsUpdatedMessageForExistingTag(): void
    {
        $action = new UpsertTagAction($this->dao);

        $result = $action->handle(['name' => 'Sale', 'tag_id' => '5']);

        $this->assertSame('Tag updated', $result);
    }

    // ── DeleteTagAction ───────────────────────────────────────────────────────

    public function testDeleteTagReturnErrorWhenTagIdMissing(): void
    {
        $action = new DeleteTagAction($this->dao);
        $this->dao->expects($this->never())->method('deleteTag');

        $result = $action->handle([]);

        $this->assertSame('Invalid tag ID', $result);
    }

    public function testDeleteTagReturnErrorWhenTagIdIsZero(): void
    {
        $action = new DeleteTagAction($this->dao);

        $result = $action->handle(['tag_id' => '0']);

        $this->assertSame('Invalid tag ID', $result);
    }

    public function testDeleteTagCallsDaoAndReturnsMessage(): void
    {
        $this->dao->expects($this->once())->method('deleteTag')->with(3);
        $action = new DeleteTagAction($this->dao);

        $result = $action->handle(['tag_id' => '3']);

        $this->assertSame('Tag deleted', $result);
    }

    // ── AddTagAssignmentAction ────────────────────────────────────────────────

    public function testAddAssignmentReturnErrorWhenStockIdMissing(): void
    {
        $action = new AddTagAssignmentAction($this->dao);
        $this->dao->expects($this->never())->method('addAssignment');

        $result = $action->handle(['tag_id' => '1']);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testAddAssignmentReturnErrorWhenTagIdMissing(): void
    {
        $action = new AddTagAssignmentAction($this->dao);

        $result = $action->handle(['stock_id' => 'SKU001']);

        $this->assertSame('Invalid tag ID', $result);
    }

    public function testAddAssignmentCallsDaoAndReturnsMessage(): void
    {
        $this->dao->expects($this->once())->method('addAssignment')->with('SKU001', 2);
        $action = new AddTagAssignmentAction($this->dao);

        $result = $action->handle(['stock_id' => 'SKU001', 'tag_id' => '2']);

        $this->assertSame('Tag assigned', $result);
    }

    // ── RemoveTagAssignmentAction ─────────────────────────────────────────────

    public function testRemoveAssignmentReturnErrorWhenStockIdMissing(): void
    {
        $action = new RemoveTagAssignmentAction($this->dao);
        $this->dao->expects($this->never())->method('removeAssignment');

        $result = $action->handle(['tag_id' => '1']);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testRemoveAssignmentCallsDaoAndReturnsMessage(): void
    {
        $this->dao->expects($this->once())->method('removeAssignment')->with('SKU001', 2);
        $action = new RemoveTagAssignmentAction($this->dao);

        $result = $action->handle(['stock_id' => 'SKU001', 'tag_id' => '2']);

        $this->assertSame('Tag removed', $result);
    }
}
