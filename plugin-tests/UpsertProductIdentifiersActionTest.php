<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertProductIdentifiersAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use PHPUnit\Framework\TestCase;

class UpsertProductIdentifiersActionTest extends TestCase
{
    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpsertProductIdentifiersAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductIdentifiersDao::class);
        $this->action = new UpsertProductIdentifiersAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle([]);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenStockIdBlank(): void
    {
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle(['stock_id' => '   ']);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleCallsUpsertWithStockId(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->anything());

        $this->action->handle(['stock_id' => 'SKU001', 'brand' => 'Acme']);
    }

    public function testHandleConvertsEmptyFieldsToNull(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle(['stock_id' => 'SKU001', 'brand' => '', 'mpn' => 'X']);

        $this->assertNull($captured['brand']);
        $this->assertSame('X', $captured['mpn']);
    }

    public function testHandleReturnsSavedMessage(): void
    {
        $result = $this->action->handle(['stock_id' => 'SKU001', 'brand' => 'Acme']);

        $this->assertSame('Identifiers saved', $result);
    }

    public function testHandleTrimsWhitespaceFromFields(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle(['stock_id' => 'SKU001', 'brand' => '  Acme  ']);

        $this->assertSame('Acme', $captured['brand']);
    }
}
