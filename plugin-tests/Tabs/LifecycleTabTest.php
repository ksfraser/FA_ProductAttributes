<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Actions\UpsertProductLifecycleAction;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Tabs\LifecycleTab;
use PHPUnit\Framework\TestCase;

class LifecycleTabTest extends TestCase
{
    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $lifecycleDao;

    /** @var LifecycleFlagDefsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $flagDefsDao;

    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $conditionDao;

    /** @var LifecycleTab */
    private $tab;

    protected function setUp(): void
    {
        $this->lifecycleDao = $this->createMock(ProductLifecycleDao::class);
        $this->flagDefsDao  = $this->createMock(LifecycleFlagDefsDao::class);
        $this->conditionDao = $this->createMock(ProductAttributesDao::class);
        $this->tab          = new LifecycleTab($this->lifecycleDao, $this->flagDefsDao, $this->conditionDao);
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->lifecycleDao->expects($this->never())->method('get');
        $this->flagDefsDao->expects($this->once())->method('listActiveFlags')->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('stock_id', $output);
    }

    public function testRenderTabContentWithStockId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
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

    public function testRenderDoesNotContainFormTag(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->flagDefsDao->expects($this->once())->method('listActiveFlags')->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<form', $output);
        $this->assertStringNotContainsString('</form>', $output);
    }

    public function testRenderConditionDropdownWithCurrentCondition(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->lifecycleDao->expects($this->once())
            ->method('get')
            ->with('SKU001')
            ->willReturn(['status' => 'active']);
        $this->flagDefsDao->method('getAssignedFlagIds')->willReturn([]);
        $this->flagDefsDao->method('listActiveFlags')->willReturn([]);
        $this->conditionDao->expects($this->once())
            ->method('getProductCondition')
            ->with('SKU001')
            ->willReturn(73);
        $this->conditionDao->expects($this->once())
            ->method('listConditions')
            ->with(true)
            ->willReturn([
                ['id' => 71, 'code' => 'new', 'label' => 'New', 'sort_order' => 10],
                ['id' => 73, 'code' => 'as_is', 'label' => 'As Is', 'sort_order' => 70],
            ]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="condition_id"', $output);
        $this->assertStringContainsString('-- Default --', $output);
        $this->assertStringContainsString('<option value="73" selected>', $output);
        $this->assertStringContainsString('<option value="71">', $output);
    }

    public function testRenderConditionDropdownFallsBackToDefaultWhenNoRow(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->flagDefsDao->method('getAssignedFlagIds')->willReturn([]);
        $this->flagDefsDao->method('listActiveFlags')->willReturn([]);
        $this->conditionDao->expects($this->once())
            ->method('getProductCondition')
            ->with('SKU001')
            ->willReturn(null);
        $this->conditionDao->expects($this->once())
            ->method('getDefaultCondition')
            ->willReturn(1);
        $this->conditionDao->expects($this->once())
            ->method('listConditions')
            ->with(true)
            ->willReturn([
                ['id' => 1, 'code' => 'new', 'label' => 'New', 'sort_order' => 10],
            ]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('<option value="1" selected>', $output);
    }

    public function testRenderSkipsConditionDropdownWhenNoActiveConditions(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->flagDefsDao->method('getAssignedFlagIds')->willReturn([]);
        $this->flagDefsDao->method('listActiveFlags')->willReturn([]);
        $this->conditionDao->expects($this->once())
            ->method('getProductCondition')
            ->with('SKU001')
            ->willReturn(null);
        $this->conditionDao->expects($this->once())
            ->method('listConditions')
            ->with(true)
            ->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('name="condition_id"', $output);
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

    public function testHandleSavePersistsConditionId(): void
    {
        $this->lifecycleDao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->arrayHasKey('status'));
        $this->flagDefsDao->expects($this->once())
            ->method('setAssignedFlags')
            ->with('SKU001', []);
        $this->conditionDao->expects($this->once())
            ->method('setProductCondition')
            ->with('SKU001', 6);

        $this->tab->handleSave('SKU001', ['status' => 'active', 'condition_id' => '6']);
    }

    public function testHandleSaveClearsConditionOnDefaultSelection(): void
    {
        $this->lifecycleDao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->arrayHasKey('status'));
        $this->flagDefsDao->expects($this->once())
            ->method('setAssignedFlags')
            ->with('SKU001', []);
        $this->conditionDao->expects($this->once())
            ->method('setProductCondition')
            ->with('SKU001', null);

        $this->tab->handleSave('SKU001', ['status' => 'active', 'condition_id' => '']);
    }

    public function testHandleSaveWithoutConditionIdLeavesConditionUntouched(): void
    {
        $this->lifecycleDao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->arrayHasKey('status'));
        $this->flagDefsDao->expects($this->once())
            ->method('setAssignedFlags')
            ->with('SKU001', []);
        $this->conditionDao->expects($this->never())
            ->method('setProductCondition');

        $this->tab->handleSave('SKU001', ['status' => 'active']);
    }

    public function testHandleDeleteCallsBothDaos(): void
    {
        $this->lifecycleDao->expects($this->once())->method('delete')->with('SKU001');
        $this->flagDefsDao->expects($this->once())->method('deleteAssignments')->with('SKU001');

        $this->tab->handleDelete('SKU001');
    }

    /**
     * Regression: lifecycle must save via the dedicated Save button without a
     * hard refresh (GitHub issue #16 / #24).
     */
    public function testPostSaveLifecyclePersistsWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action'             => 'save_product_lifecycle',
            'pa_lifecycle_save'  => 'Save',
            'status'             => 'discontinued',
        ];

        $this->lifecycleDao->expects($this->once())
            ->method('upsert')
            ->with('SKU001', $this->arrayHasKey('status'));
        $this->flagDefsDao->expects($this->once())
            ->method('setAssignedFlags')
            ->with('SKU001', []);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('name="pa_lifecycle_save"', $output);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    public function testPostWithoutLifecycleSaveButtonDoesNotPersist(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'action' => 'save_product_lifecycle',
            'status' => 'draft',
        ];

        $this->lifecycleDao->expects($this->never())
            ->method('upsert');
        $this->flagDefsDao->expects($this->never())
            ->method('setAssignedFlags');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
