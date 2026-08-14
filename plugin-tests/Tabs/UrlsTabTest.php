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

        $this->assertStringContainsString('URLs', $output);
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

    /**
     * Regression: POST add must persist via the tab handler without a
     * header('Location') hard refresh (GitHub issue #12 / #24).
     */
    public function testPostAddUrlPersistsWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_url_add' => 'Add URL', 'url' => 'https://example.com', 'description' => 'Demo'];

        $this->dao->expects($this->once())
            ->method('add')
            ->with('SKU001', 'https://example.com', 'Demo');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('URLs', $output);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    /**
     * Regression: POST delete must delete via the tab handler without a
     * header('Location') hard refresh (GitHub issue #13 / #24).
     */
    public function testPostDeleteUrlDeletesWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_url_delete' => '7'];

        $this->dao->expects($this->once())
            ->method('delete')
            ->with(7);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    public function testPostAddUrlIgnoresEmptyUrl(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_url_add' => 'Add URL', 'url' => '   ', 'description' => ''];

        $this->dao->expects($this->never())
            ->method('add');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
