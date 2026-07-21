<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Actions\UpsertProductLifecycleAction;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Tabs\LifecycleTab;
use PHPUnit\Framework\TestCase;

class LifecycleTabTest extends TestCase
{
    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $lifecycleDao;

    /** @var LifecycleFlagDefsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $flagDefsDao;

    /** @var LifecycleTab */
    private $tab;

    protected function setUp(): void
    {
        $this->lifecycleDao = $this->createMock(ProductLifecycleDao::class);
        $this->flagDefsDao  = $this->createMock(LifecycleFlagDefsDao::class);
        $this->tab          = new LifecycleTab($this->lifecycleDao, $this->flagDefsDao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_lifecycle', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_lifecycle', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Lifecycle', $this->tab->getTabLabel());
    }

    public function testRenderTabContentEmptyStockId(): void
    {
        $this->lifecycleDao->expects($this->never())->method('get');
        $this->flagDefsDao->expects($this->once())->method('listActiveFlags')->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('');
        $output = ob_get_clean();

        $this->assertStringContainsString('stock_id', $output);
    }

    public function testRenderTabContentWithStockId(): void
    {
        $this->lifecycleDao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn(['status' => 'active']);
        $this->flagDefsDao->expects($this->once())
            ->method('getAssignedFlagIds')
            ->with('SKU001')
            ->willReturn([]);
        $this->flagDefsDao->expects($this->once())
            ->method('listActiveFlags')
            ->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Active', $output);
    }

    public function testHandleSaveCallsLifecycleUpsert(): void
    {
        $this->lifecycleDao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->arrayHasKey('status'));
        $this->flagDefsDao->expects($this->once())
            ->method('setAssignedFlags')
            ->with('SKU001', []);

        $this->tab->handleSave('SKU001', ['status' => 'discontinued']);
    }

    public function testHandleSaveWithFlagIds(): void
    {
        $this->lifecycleDao->expects($this->once())->method('upsert');
        $this->flagDefsDao->expects($this->once())
            ->method('setAssignedFlags')
            ->with('SKU001', [1, 2]);

        $this->tab->handleSave('SKU001', ['status' => 'active', 'lifecycle_flags' => ['1', '2']]);
    }

    public function testHandleDeleteCallsBothDaos(): void
    {
        $this->lifecycleDao->expects($this->once())->method('delete')->with('SKU001');
        $this->flagDefsDao->expects($this->once())->method('deleteAssignments')->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }
}
