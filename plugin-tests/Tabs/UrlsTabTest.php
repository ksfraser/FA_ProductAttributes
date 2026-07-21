<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\MediaAttachmentsDao;
use Ksfraser\FA_ProductAttributes\Tabs\UrlsTab;
use PHPUnit\Framework\TestCase;

class UrlsTabTest extends TestCase
{
    /** @var MediaAttachmentsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var UrlsTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(MediaAttachmentsDao::class);
        $this->tab = new UrlsTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_urls', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_urls', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('URLs', $this->tab->getTabLabel());
    }

    public function testRenderTabContentEmptyStockId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->never())->method('listByStockId');

        ob_start();
        $this->tab->renderTabContent('');
        $output = ob_get_clean();

        $this->assertStringContainsString('Media Attachments', $output);
    }

    public function testRenderTabContentWithItems(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->once())
            ->method('listByStockId')
            ->with('SKU001')
            ->willReturn([
                ['id' => 1, 'url' => 'https://example.com', 'description' => 'Test', 'created_date' => '2025-01-01'],
            ]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('https://example.com', $output);
    }

    public function testHandleDeleteDeletesAllItems(): void
    {
        $this->dao->expects($this->once())
            ->method('listByStockId')
            ->with('SKU001')
            ->willReturn([['id' => 1], ['id' => 2]]);
        $this->dao->expects($this->exactly(2))
            ->method('delete');

        $this->tab->handleDelete('SKU001');
    }
}
