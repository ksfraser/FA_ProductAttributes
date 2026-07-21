<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\SetMediaVariationLinksAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use PHPUnit\Framework\TestCase;

class SetMediaVariationLinksActionTest extends TestCase
{
    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var SetMediaVariationLinksAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductMediaDao::class);
        $this->action = new SetMediaVariationLinksAction($this->dao);
    }

    public function testHandleReturnErrorWhenMediaIdMissing(): void
    {
        $this->dao->expects($this->never())->method('setVariationLinks');
        $result = $this->action->handle([]);
        $this->assertSame('Invalid media ID', $result);
    }

    public function testHandleReturnErrorWhenMediaIdZero(): void
    {
        $this->dao->expects($this->never())->method('setVariationLinks');
        $result = $this->action->handle(['media_id' => '0']);
        $this->assertSame('Invalid media ID', $result);
    }

    public function testHandleCallsSetVariationLinksWithEmptyArray(): void
    {
        $this->dao->expects($this->once())
            ->method('setVariationLinks')
            ->with(42, []);

        $result = $this->action->handle(['media_id' => '42']);
        $this->assertSame('Media variation links updated', $result);
    }

    public function testHandlePassesVariationStockIds(): void
    {
        $this->dao->expects($this->once())
            ->method('setVariationLinks')
            ->with(42, ['VAR001', 'VAR002']);

        $this->action->handle([
            'media_id'            => '42',
            'variation_stock_ids' => ['VAR001', 'VAR002'],
        ]);
    }

    public function testHandleFiltersEmptyVariationIds(): void
    {
        $this->dao->expects($this->once())
            ->method('setVariationLinks')
            ->with(42, ['VAR001']);

        $this->action->handle([
            'media_id'            => '42',
            'variation_stock_ids' => ['VAR001', '', '  ', null],
        ]);
    }

    public function testHandleTrimsVariationIds(): void
    {
        $this->dao->expects($this->once())
            ->method('setVariationLinks')
            ->with(42, ['VAR001']);

        $this->action->handle([
            'media_id'            => '42',
            'variation_stock_ids' => ['  VAR001  '],
        ]);
    }
}
