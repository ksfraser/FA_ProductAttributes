<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertWarrantyAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use PHPUnit\Framework\TestCase;

class UpsertWarrantyActionTest extends TestCase
{
    /** @var ProductWarrantyDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpsertWarrantyAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ProductWarrantyDao::class);
        $this->action = new UpsertWarrantyAction($this->dao);
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
        $result = $this->action->handle('   ', []);
        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleDefaultsWarrantyTypeToNoneWhenMissing(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', []);

        $this->assertSame('none', $captured['warranty_type']);
    }

    public function testHandleDefaultsWarrantyTypeToNoneWhenInvalid(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['warranty_type' => 'invalid_type']);

        $this->assertSame('none', $captured['warranty_type']);
    }

    public function testHandlePassesValidWarrantyType(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['warranty_type' => 'lifetime']);

        $this->assertSame('lifetime', $captured['warranty_type']);
    }

    public function testHandleDefaultsDurationUnitsToMonths(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', []);

        $this->assertSame('months', $captured['manufacturer_duration_unit']);
        $this->assertSame('months', $captured['extended_duration_unit']);
        $this->assertSame('months', $captured['third_party_duration_unit']);
    }

    public function testHandleDefaultsInvalidDurationUnitsToMonths(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', [
            'manufacturer_duration_unit' => 'decades',
            'extended_duration_unit'     => 'centuries',
            'third_party_duration_unit'  => 'millennia',
        ]);

        $this->assertSame('months', $captured['manufacturer_duration_unit']);
        $this->assertSame('months', $captured['extended_duration_unit']);
        $this->assertSame('months', $captured['third_party_duration_unit']);
    }

    public function testHandleAcceptsValidDurationUnits(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', [
            'manufacturer_duration_unit' => 'years',
            'extended_duration_unit'     => 'days',
            'third_party_duration_unit'  => 'months',
        ]);

        $this->assertSame('years', $captured['manufacturer_duration_unit']);
        $this->assertSame('days', $captured['extended_duration_unit']);
        $this->assertSame('months', $captured['third_party_duration_unit']);
    }

    public function testHandleNullsEmptyDurations(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', []);

        $this->assertNull($captured['manufacturer_duration']);
        $this->assertNull($captured['extended_duration']);
        $this->assertNull($captured['third_party_duration']);
    }

    public function testHandleCastsDurationToInt(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', [
            'manufacturer_duration' => '12',
            'extended_duration'     => '24',
            'third_party_duration'  => '36',
        ]);

        $this->assertSame(12, $captured['manufacturer_duration']);
        $this->assertSame(24, $captured['extended_duration']);
        $this->assertSame(36, $captured['third_party_duration']);
    }

    public function testHandleNullsEmptyNotes(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['lifetime_notes' => '', 'warranty_notes' => '']);

        $this->assertNull($captured['lifetime_notes']);
        $this->assertNull($captured['warranty_notes']);
    }

    public function testHandleTrimsNotes(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['lifetime_notes' => '  forever  ']);

        $this->assertSame('forever', $captured['lifetime_notes']);
    }

    public function testHandleReturnsSavedMessage(): void
    {
        $this->dao->expects($this->once())->method('upsert');

        $result = $this->action->handle('SKU001', ['warranty_type' => 'manufacturer']);

        $this->assertSame('Warranty saved', $result);
    }

    public function testHandleModifiesWhitespaceNotesToNull(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with($this->anything(), $this->callback(function ($d) use (&$captured) {
                $captured = $d;
                return true;
            }));

        $this->action->handle('SKU001', ['lifetime_notes' => '  ', 'warranty_notes' => "\t\n"]);

        $this->assertNull($captured['lifetime_notes']);
        $this->assertNull($captured['warranty_notes']);
    }
}
