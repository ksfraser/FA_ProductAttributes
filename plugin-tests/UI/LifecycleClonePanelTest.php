<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\LifecycleClonePanel;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use PHPUnit\Framework\TestCase;

class LifecycleClonePanelTest extends TestCase
{
    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $lifecycleDao;

    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    /** @var LifecycleClonePanel */
    private $panel;

    protected function setUp(): void
    {
        $this->lifecycleDao  = $this->createMock(ProductLifecycleDao::class);
        $this->variationsDao = $this->createMock(VariationsDao::class);
        $this->panel = new LifecycleClonePanel($this->lifecycleDao, $this->variationsDao);
    }

    public function testRenderOutputsNothingWhenEmptyStockId(): void
    {
        $this->variationsDao->expects($this->never())->method('getProductVariations');

        ob_start();
        $this->panel->render('');
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    public function testRenderOutputsNothingWhenNoVariations(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    public function testRenderOutputsFieldsetWhenVariationsExist(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([['stock_id' => 'PARENT-RED']]);
        $this->lifecycleDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('<fieldset>', $html);
    }

    public function testRenderContainsCorrectHiddenActionInput(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([['stock_id' => 'PARENT-RED']]);
        $this->lifecycleDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="clone_lifecycle_to_variations"', $html);
    }

    public function testRenderShowsStatusInSummaryColumn(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->lifecycleDao->method('get')->willReturn(['status' => 'discontinued', 'is_featured' => 0]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('discontinued', $html);
    }

    public function testRenderShowsActiveFlagsInSummaryColumn(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->lifecycleDao->method('get')->willReturn([
            'status'          => 'active',
            'is_clearance'    => 1,
            'is_special_order' => 0,
        ]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('Clearance', $html);
    }
}
