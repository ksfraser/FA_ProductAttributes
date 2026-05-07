<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\IdentifiersClonePanel;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use PHPUnit\Framework\TestCase;

class IdentifiersClonePanelTest extends TestCase
{
    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $identifiersDao;

    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    /** @var IdentifiersClonePanel */
    private $panel;

    protected function setUp(): void
    {
        $this->identifiersDao = $this->createMock(ProductIdentifiersDao::class);
        $this->variationsDao  = $this->createMock(VariationsDao::class);
        $this->panel = new IdentifiersClonePanel($this->identifiersDao, $this->variationsDao);
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
        $this->identifiersDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('<fieldset>', $html);
    }

    public function testRenderContainsCorrectHiddenActionInput(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([['stock_id' => 'PARENT-RED']]);
        $this->identifiersDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="clone_identifiers_to_variations"', $html);
    }

    public function testRenderContainsVariationCheckboxes(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED',  'description' => 'Red'],
            ['stock_id' => 'PARENT-BLUE', 'description' => 'Blue'],
        ]);
        $this->identifiersDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('PARENT-RED', $html);
        $this->assertStringContainsString('PARENT-BLUE', $html);
    }

    public function testRenderShowsExistingBrandAndMpnForEachVariation(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED'],
        ]);
        $this->identifiersDao->method('get')->with('PARENT-RED')->willReturn([
            'brand' => 'Acme',
            'mpn'   => 'X-100',
        ]);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringContainsString('Acme', $html);
        $this->assertStringContainsString('X-100', $html);
    }

    public function testRenderEscapesVariationIdInCheckboxValue(): void
    {
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => '"<xss>"'],
        ]);
        $this->identifiersDao->method('get')->willReturn(null);

        ob_start();
        $this->panel->render('PARENT');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('"<xss>"', $html);
    }
}
