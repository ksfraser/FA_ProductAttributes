<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\Tabs\WarrantyTab;
use PHPUnit\Framework\TestCase;

class WarrantyTabTest extends TestCase
{
    /** @var ProductWarrantyDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var WarrantyTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductWarrantyDao::class);
        $this->tab = new WarrantyTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_warranty', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_warranty', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Warranty', $this->tab->getTabLabel());
    }

    public function testHandleSaveCreatesActionAndCallsHandle(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert');

        $this->tab->handleSave('SKU001', ['warranty_type' => 'manufacturer']);
    }

    public function testHandleDeleteDelegatesToDao(): void
    {
        $this->dao->expects($this->once())
            ->method('delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }
}
