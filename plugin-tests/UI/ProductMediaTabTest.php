<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ProductMediaTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use PHPUnit\Framework\TestCase;

class ProductMediaTabTest extends TestCase
{
    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $mediaDao;

    /** @var VariationsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $variationsDao;

    /** @var ProductMediaTab */
    private $tab;

    protected function setUp(): void
    {
        $this->mediaDao      = $this->createMock(ProductMediaDao::class);
        $this->variationsDao = $this->createMock(VariationsDao::class);
        $this->tab = new ProductMediaTab($this->mediaDao, $this->variationsDao);
    }

    public function testRenderOutputsNoProductSelectedWhenEmptyStockId(): void
    {
        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringContainsString('No product selected', $html);
    }

    public function testRenderOutputsNoMediaMessageWhenEmpty(): void
    {
        $this->mediaDao->method('getProductMedia')->willReturn([]);
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('No media added', $html);
    }

    public function testRenderShowsMediaUrl(): void
    {
        $this->mediaDao->method('getProductMedia')->willReturn([
            ['id' => 1, 'url' => 'https://example.com/img.jpg', 'media_type' => 'image',
             'is_primary' => 0, 'alt_text' => ''],
        ]);
        $this->mediaDao->method('getVariationLinks')->willReturn([]);
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('https://example.com/img.jpg', $html);
    }

    public function testRenderShowsDeleteForm(): void
    {
        $this->mediaDao->method('getProductMedia')->willReturn([
            ['id' => 5, 'url' => 'https://x.com/a.jpg', 'media_type' => 'image',
             'is_primary' => 0, 'alt_text' => ''],
        ]);
        $this->mediaDao->method('getVariationLinks')->willReturn([]);
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="delete_product_media"', $html);
    }

    public function testRenderShowsAddForm(): void
    {
        $this->mediaDao->method('getProductMedia')->willReturn([]);
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="add_product_media"', $html);
        $this->assertStringContainsString('name="url"', $html);
    }

    public function testRenderShowsVariationCheckboxesWhenVariationsExist(): void
    {
        $this->mediaDao->method('getProductMedia')->willReturn([
            ['id' => 3, 'url' => 'https://x.com/b.jpg', 'media_type' => 'image',
             'is_primary' => 0, 'alt_text' => ''],
        ]);
        $this->mediaDao->method('getVariationLinks')->willReturn(['PARENT-RED']);
        $this->variationsDao->method('getProductVariations')->willReturn([
            ['stock_id' => 'PARENT-RED',  'description' => 'Red'],
            ['stock_id' => 'PARENT-BLUE', 'description' => 'Blue'],
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="set_media_variation_links"', $html);
        $this->assertStringContainsString('PARENT-RED', $html);
        $this->assertStringContainsString('PARENT-BLUE', $html);
    }

    public function testRenderMarksPrimaryMediaItem(): void
    {
        $this->mediaDao->method('getProductMedia')->willReturn([
            ['id' => 1, 'url' => 'https://x.com/img.jpg', 'media_type' => 'image',
             'is_primary' => 1, 'alt_text' => ''],
        ]);
        $this->mediaDao->method('getVariationLinks')->willReturn([]);
        $this->variationsDao->method('getProductVariations')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('Primary', $html);
    }
}
