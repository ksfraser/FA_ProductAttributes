<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\AddProductMediaAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use PHPUnit\Framework\TestCase;

class AddProductMediaActionTest extends TestCase
{
    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var AddProductMediaAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductMediaDao::class);
        $this->action = new AddProductMediaAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('addMedia');
        $result = $this->action->handle([]);
        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenUrlMissing(): void
    {
        $this->dao->expects($this->never())->method('addMedia');
        $result = $this->action->handle(['stock_id' => 'SKU001']);
        $this->assertSame('Media URL is required', $result);
    }

    public function testHandleReturnErrorWhenUrlBlank(): void
    {
        $this->dao->expects($this->never())->method('addMedia');
        $result = $this->action->handle(['stock_id' => 'SKU001', 'url' => '   ']);
        $this->assertSame('Media URL is required', $result);
    }

    public function testHandleCallsAddMediaWithDefaults(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with('SKU001', 'https://example.com/img.jpg', '', 0, 'image', false, null);

        $result = $this->action->handle([
            'stock_id' => 'SKU001',
            'url'      => 'https://example.com/img.jpg',
        ]);

        $this->assertSame('Media added', $result);
    }

    public function testHandlePassesAllFields(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with(
                'SKU001',
                'https://example.com/vid.mp4',
                'Product video',
                2,
                'video',
                true,
                null
            );

        $this->action->handle([
            'stock_id'   => 'SKU001',
            'url'        => 'https://example.com/vid.mp4',
            'alt_text'   => 'Product video',
            'sort_order' => '2',
            'media_type' => 'video',
            'is_primary' => '1',
        ]);
    }

    public function testHandleDefaultsInvalidMediaTypeToImage(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), 'image', $this->anything(), $this->anything());

        $this->action->handle([
            'stock_id'   => 'SKU001',
            'url'        => 'https://example.com/file',
            'media_type' => 'audio',
        ]);
    }

    public function testHandleTrimsFields(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with($this->callback(function ($s) use (&$captured) {
                $captured = $s;
                return true;
            }), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything());

        $this->action->handle([
            'stock_id' => '  SKU001  ',
            'url'      => '  https://example.com/img.jpg  ',
        ]);
    }

    public function testHandlePassesDownloadUrlIfPresent(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with('SKU001', 'https://example.com/img.jpg', '', 0, 'image', false, 'https://example.com/download');

        $this->action->handle([
            'stock_id'     => 'SKU001',
            'url'          => 'https://example.com/img.jpg',
            'download_url' => 'https://example.com/download',
        ]);
    }

    public function testHandleNullsDownloadUrlIfEmpty(): void
    {
        $this->dao->expects($this->once())
            ->method('addMedia')
            ->with('SKU001', 'https://example.com/img.jpg', '', 0, 'image', false, null);

        $this->action->handle([
            'stock_id'     => 'SKU001',
            'url'          => 'https://example.com/img.jpg',
            'download_url' => '  ',
        ]);
    }
}
