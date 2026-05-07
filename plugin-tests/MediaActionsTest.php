<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\AddProductMediaAction;
use Ksfraser\FA_ProductAttributes\Actions\DeleteProductMediaAction;
use Ksfraser\FA_ProductAttributes\Actions\SetMediaVariationLinksAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use PHPUnit\Framework\TestCase;

class MediaActionsTest extends TestCase
{
    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductMediaDao::class);
    }

    // ── AddProductMediaAction ─────────────────────────────────────────────────

    public function testAddMediaReturnErrorWhenStockIdMissing(): void
    {
        $action = new AddProductMediaAction($this->dao);
        $this->dao->expects($this->never())->method('addMedia');

        $result = $action->handle(['url' => 'https://example.com/img.jpg']);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testAddMediaReturnErrorWhenUrlMissing(): void
    {
        $action = new AddProductMediaAction($this->dao);
        $this->dao->expects($this->never())->method('addMedia');

        $result = $action->handle(['stock_id' => 'SKU001']);

        $this->assertSame('Media URL is required', $result);
    }

    public function testAddMediaReturnErrorWhenUrlBlank(): void
    {
        $action = new AddProductMediaAction($this->dao);

        $result = $action->handle(['stock_id' => 'SKU001', 'url' => '   ']);

        $this->assertSame('Media URL is required', $result);
    }

    public function testAddMediaDefaultsTypeToImage(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with('SKU001', $this->anything(), $this->anything(), $this->anything(), 'image', $this->anything())
            ->willReturn(1);

        $action = new AddProductMediaAction($this->dao);
        $action->handle(['stock_id' => 'SKU001', 'url' => 'https://example.com/img.jpg']);
    }

    public function testAddMediaNormalizesInvalidTypeToImage(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), 'image', $this->anything())
            ->willReturn(1);

        $action = new AddProductMediaAction($this->dao);
        $action->handle(['stock_id' => 'SKU001', 'url' => 'https://example.com/img.jpg', 'media_type' => 'invalid']);
    }

    public function testAddMediaAcceptsValidMediaTypes(): void
    {
        foreach (['image', 'video', 'document'] as $type) {
            $dao = $this->createMock(ProductMediaDao::class);
            $dao->expects($this->once())
                ->method('addMedia')
                ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), $type, $this->anything())
                ->willReturn(1);

            $action = new AddProductMediaAction($dao);
            $action->handle(['stock_id' => 'SKU001', 'url' => 'https://example.com/f', 'media_type' => $type]);
        }
    }

    public function testAddMediaReturnsSuccessMessage(): void
    {
        $this->dao->method('addMedia')->willReturn(7);
        $action = new AddProductMediaAction($this->dao);

        $result = $action->handle(['stock_id' => 'SKU001', 'url' => 'https://example.com/img.jpg']);

        $this->assertSame('Media added', $result);
    }

    // ── DeleteProductMediaAction ──────────────────────────────────────────────

    public function testDeleteMediaReturnErrorWhenMediaIdMissing(): void
    {
        $action = new DeleteProductMediaAction($this->dao);
        $this->dao->expects($this->never())->method('deleteMedia');

        $result = $action->handle([]);

        $this->assertSame('Invalid media ID', $result);
    }

    public function testDeleteMediaReturnErrorWhenMediaIdIsZero(): void
    {
        $action = new DeleteProductMediaAction($this->dao);

        $result = $action->handle(['media_id' => '0']);

        $this->assertSame('Invalid media ID', $result);
    }

    public function testDeleteMediaCallsDaoAndReturnsMessage(): void
    {
        $this->dao->expects($this->once())->method('deleteMedia')->with(5);
        $action = new DeleteProductMediaAction($this->dao);

        $result = $action->handle(['media_id' => '5']);

        $this->assertSame('Media deleted', $result);
    }

    // ── SetMediaVariationLinksAction ──────────────────────────────────────────

    public function testSetLinksReturnErrorWhenMediaIdMissing(): void
    {
        $action = new SetMediaVariationLinksAction($this->dao);
        $this->dao->expects($this->never())->method('setVariationLinks');

        $result = $action->handle([]);

        $this->assertSame('Invalid media ID', $result);
    }

    public function testSetLinksCallsDaoWithFilteredIds(): void
    {
        $this->dao->expects($this->once())
            ->method('setVariationLinks')
            ->with(5, ['PARENT-RED', 'PARENT-BLUE']);

        $action = new SetMediaVariationLinksAction($this->dao);
        $action->handle([
            'media_id'            => '5',
            'variation_stock_ids' => ['PARENT-RED', '', ' ', 'PARENT-BLUE'],
        ]);
    }

    public function testSetLinksAcceptsEmptyVariationsList(): void
    {
        $this->dao->expects($this->once())
            ->method('setVariationLinks')
            ->with(5, []);

        $action = new SetMediaVariationLinksAction($this->dao);
        $result = $action->handle(['media_id' => '5']);

        $this->assertSame('Media variation links updated', $result);
    }
}
