<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Tabs\ShippingTab;
use PHPUnit\Framework\TestCase;

class ShippingTabTest extends TestCase
{
    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ShippingTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ShippingAttributesDao::class);
        $this->tab = new ShippingTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('shipping_attributes', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('shipping_attributes', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Shipping', $this->tab->getTabLabel());
    }

    public function testHandleSaveCreatesActionAndCallsHandle(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert');

        $this->tab->handleSave('SKU001', ['length' => '30']);
    }

    public function testHandleDeleteDelegatesToDao(): void
    {
        $this->dao->expects($this->once())
            ->method('delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }
}
