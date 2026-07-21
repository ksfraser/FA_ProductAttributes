<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Tabs\IdentifiersTab;
use PHPUnit\Framework\TestCase;

class IdentifiersTabTest extends TestCase
{
    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var IdentifiersTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductIdentifiersDao::class);
        $this->tab = new IdentifiersTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_identifiers', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_identifiers', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Identifiers', $this->tab->getTabLabel());
    }

    public function testHandleSaveCreatesActionAndCallsHandle(): void
    {
        $this->dao->expects($this->once())
            ->method('upsert');

        $this->tab->handleSave('SKU001', ['brand' => 'Acme']);
    }

    public function testHandleDeleteDelegatesToDao(): void
    {
        $this->dao->expects($this->once())
            ->method('delete')
            ->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }
}
