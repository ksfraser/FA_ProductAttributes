<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertProductLifecycleAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use PHPUnit\Framework\TestCase;

class UpsertProductLifecycleActionTest extends TestCase
{
    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpsertProductLifecycleAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductLifecycleDao::class);
        $this->action = new UpsertProductLifecycleAction($this->dao);
    }

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle('', []);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenStockIdBlank(): void
    {
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle('', []);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleAcceptsValidStatus(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['status' => 'discontinued']);

        $this->assertSame('discontinued', $captured['status']);
    }

    public function testHandleDefaultsInvalidStatusToActive(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['status' => 'invalid_value']);

        $this->assertSame('active', $captured['status']);
    }

    public function testHandleCastsBooleanFlagsToInt(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['is_featured' => '1', 'is_clearance' => '']);

        $this->assertSame(1, $captured['is_featured']);
        $this->assertSame(0, $captured['is_clearance']);
    }

    public function testHandleAcceptsValidDateStrings(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['available_from' => '2025-01-01',
            'discontinue_on' => '2025-12-31',]);

        $this->assertSame('2025-01-01', $captured['available_from']);
        $this->assertSame('2025-12-31', $captured['discontinue_on']);
    }

    public function testHandleNullsInvalidDateStrings(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['available_from' => 'not-a-date',
            'discontinue_on' => '01/01/2025',]);

        $this->assertNull($captured['available_from']);
        $this->assertNull($captured['discontinue_on']);
    }

    public function testHandleReturnsSavedMessage(): void
    {
        $result = $this->action->handle('SKU001', []);

        $this->assertSame('Lifecycle saved', $result);
    }
}
