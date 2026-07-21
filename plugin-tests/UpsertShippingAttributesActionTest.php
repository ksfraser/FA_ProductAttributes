<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertShippingAttributesAction;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use PHPUnit\Framework\TestCase;

class UpsertShippingAttributesActionTest extends TestCase
{
    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UpsertShippingAttributesAction */
    private $action;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ShippingAttributesDao::class);
        $this->action = new UpsertShippingAttributesAction($this->dao);
    }

    // ── validation ────────────────────────────────────────────────────────────

    public function testHandleReturnErrorWhenStockIdMissing(): void
    {
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle('', []);

        $this->assertSame('Invalid stock ID', $result);
    }

    public function testHandleReturnErrorWhenStockIdBlank(): void
    {
        $this->dao->expects($this->never())->method('upsert');

        $result = $this->action->handle("   ", []);

        $this->assertSame('Invalid stock ID', $result);
    }

    // ── success paths ─────────────────────────────────────────────────────────

    public function testHandleCallsUpsertAndReturnsSuccessMessage(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->isType('array'));

        $result = $this->action->handle('SKU001', ['length'   => '30',
            'width'    => '20',
            'height'   => '10',
            'weight'   => '1.5',]);

        $this->assertSame('Shipping attributes saved', $result);
    }

    public function testHandleDefaultsToMetricUnitsWhenAbsent(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', []);

        $this->assertSame('cm', $captured['dim_unit']);
        $this->assertSame('kg', $captured['weight_unit']);
        $this->assertSame('C',  $captured['temp_unit']);
    }

    public function testHandleRejectsInvalidDimUnit(): void
    {
        $captured = null;
        $this->dao->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['dim_unit' => 'furlong']);

        $this->assertSame('cm', $captured['dim_unit']);
    }

    public function testHandleRejectsInvalidWeightUnit(): void
    {
        $captured = null;
        $this->dao->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['weight_unit' => 'stone']);

        $this->assertSame('kg', $captured['weight_unit']);
    }

    // ── hazmat data ───────────────────────────────────────────────────────────

    public function testHandleStoresHazmatData(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['is_hazardous'         => '1',
            'hazmat_class'         => '3',
            'un_number'            => '1170',
            'proper_shipping_name' => 'Ethanol',
            'packing_group'        => 'II',]);

        $this->assertSame(1,         $captured['is_hazardous']);
        $this->assertSame('3',       $captured['hazmat_class']);
        $this->assertSame('1170',    $captured['un_number']);
        $this->assertSame('Ethanol', $captured['proper_shipping_name']);
        $this->assertSame('II',      $captured['packing_group']);
    }

    public function testHandleRejectsInvalidPackingGroup(): void
    {
        $captured = null;
        $this->dao->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['packing_group' => 'IV']);

        $this->assertNull($captured['packing_group']);
    }

    // ── temperature data ──────────────────────────────────────────────────────

    public function testHandleStoresTemperatureData(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['temperature_sensitive' => '1',
            'temp_min'              => '-18',
            'temp_max'              => '4',
            'temp_unit'             => 'C',]);

        $this->assertSame(1,    $captured['temperature_sensitive']);
        $this->assertSame(-18.0, $captured['temp_min']);
        $this->assertSame(4.0,   $captured['temp_max']);
    }

    // ── nullable fields ───────────────────────────────────────────────────────

    public function testHandleConvertsEmptyStringFieldsToNull(): void
    {
        $captured = null;
        $this->dao->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['length'   => '',
            'hs_code'  => '   ',]);

        $this->assertNull($captured['length']);
        $this->assertNull($captured['hs_code']);
    }

    // ── customs data ──────────────────────────────────────────────────────────

    public function testHandleStoresCustomsData(): void
    {
        $captured = null;
        $this->dao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['hs_code'             => '6109.10.00',
            'country_of_origin'   => 'Canada',
            'customs_description' => 'Cotton T-shirt',
            'declared_value'      => '15.99',]);

        $this->assertSame('6109.10.00',    $captured['hs_code']);
        $this->assertSame('Canada',         $captured['country_of_origin']);
        $this->assertSame('Cotton T-shirt', $captured['customs_description']);
        $this->assertSame(15.99,            $captured['declared_value']);
    }

    // ── handling flags ────────────────────────────────────────────────────────

    public function testHandleStoresHandlingFlags(): void
    {
        $captured = null;
        $this->dao->method('upsert')
            ->with('SKU001', $this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }));

        $this->action->handle('SKU001', ['is_fragile'   => '1',
            'is_stackable' => '0',
            'is_oversize'  => '1',
            'is_perishable'=> '1',]);

        $this->assertSame(1, $captured['is_fragile']);
        $this->assertSame(0, $captured['is_stackable']);
        $this->assertSame(1, $captured['is_oversize']);
        $this->assertSame(1, $captured['is_perishable']);
    }
}
