<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ProductAttributesSummaryTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use PHPUnit\Framework\TestCase;

class ProductAttributesSummaryTabTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $coreDao;

    /** @var ProductIdentifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $identifiersDao;

    /** @var ProductLifecycleDao|\PHPUnit\Framework\MockObject\MockObject */
    private $lifecycleDao;

    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $tagsDao;

    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $mediaDao;

    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingDao;

    /** @var ProductAttributesSummaryTab */
    private $tab;

    protected function setUp(): void
    {
        $this->coreDao        = $this->createMock(ProductAttributesDao::class);
        $this->identifiersDao = $this->createMock(ProductIdentifiersDao::class);
        $this->lifecycleDao   = $this->createMock(ProductLifecycleDao::class);
        $this->tagsDao        = $this->createMock(ProductTagsDao::class);
        $this->mediaDao       = $this->createMock(ProductMediaDao::class);
        $this->shippingDao    = $this->createMock(ShippingAttributesDao::class);

        $this->tab = new ProductAttributesSummaryTab(
            $this->coreDao,
            $this->identifiersDao,
            $this->lifecycleDao,
            $this->tagsDao,
            $this->mediaDao,
            $this->shippingDao
        );
    }

    public function testRenderOutputsNoProductSelectedWhenEmptyStockId(): void
    {
        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringContainsString('No product selected', $html);
    }

    public function testRenderShowsStockIdInHeading(): void
    {
        $this->stubAllDaosEmpty();

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('SKU001', $html);
    }

    public function testRenderShowsIdentifiersSectionHeading(): void
    {
        $this->stubAllDaosEmpty();

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('Identifiers', $html);
    }

    public function testRenderShowsLifecycleSectionHeading(): void
    {
        $this->stubAllDaosEmpty();

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('Lifecycle', $html);
    }

    public function testRenderShowsBrandWhenIdentifiersExist(): void
    {
        $this->identifiersDao->method('get')->willReturn(['brand' => 'Acme Corp']);
        $this->lifecycleDao->method('get')->willReturn(null);
        $this->tagsDao->method('getProductTags')->willReturn([]);
        $this->mediaDao->method('getProductMedia')->willReturn([]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('Acme Corp', $html);
    }

    public function testRenderShowsTagNames(): void
    {
        $this->identifiersDao->method('get')->willReturn(null);
        $this->lifecycleDao->method('get')->willReturn(null);
        $this->tagsDao->method('getProductTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale'],
            ['id' => 2, 'name' => 'New'],
        ]);
        $this->mediaDao->method('getProductMedia')->willReturn([]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('On Sale', $html);
        $this->assertStringContainsString('New', $html);
    }

    public function testRenderShowsMediaUrl(): void
    {
        $this->identifiersDao->method('get')->willReturn(null);
        $this->lifecycleDao->method('get')->willReturn(null);
        $this->tagsDao->method('getProductTags')->willReturn([]);
        $this->mediaDao->method('getProductMedia')->willReturn([
            ['id' => 1, 'url' => 'https://example.com/photo.jpg', 'media_type' => 'image', 'is_primary' => 1],
        ]);
        $this->shippingDao->method('get')->willReturn(null);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('https://example.com/photo.jpg', $html);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function stubAllDaosEmpty(): void
    {
        $this->identifiersDao->method('get')->willReturn(null);
        $this->lifecycleDao->method('get')->willReturn(null);
        $this->tagsDao->method('getProductTags')->willReturn([]);
        $this->mediaDao->method('getProductMedia')->willReturn([]);
        $this->shippingDao->method('get')->willReturn(null);
    }
}
