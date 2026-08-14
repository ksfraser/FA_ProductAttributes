<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Tabs\MediaTab;
use PHPUnit\Framework\TestCase;

class MediaTabTest extends TestCase
{
    /** @var ProductMediaDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var MediaTab */
    private $tab;

    public static function setUpBeforeClass(): void
    {
        if (!defined('TB_PREF')) {
            define('TB_PREF', '0_');
        }
        if (!function_exists('company_path')) {
            function company_path() { return '/tmp/company'; }
        }
        if (!function_exists('item_img_name')) {
            function item_img_name($id) { return preg_replace('/[^a-zA-Z0-9_-]/', '', $id); }
        }
        if (!function_exists('glob')) {
            // glob is a built-in, but define fallback
        }
    }

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductMediaDao::class);
        $this->tab = new MediaTab($this->dao);
    }

    public function testGetName(): void
    {
        $this->assertSame('product_media', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('product_media', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Media', $this->tab->getTabLabel());
    }

    public function testRenderTabContentEmptyStockId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->never())->method('getProductMedia');

        ob_start();
        $this->tab->renderTabContent('');
        $output = ob_get_clean();

        $this->assertStringContainsString('Primary Image', $output);
    }

    public function testRenderTabContentWithMediaItems(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->dao->expects($this->once())
            ->method('getProductMedia')
            ->with('SKU001')
            ->willReturn([
                ['id' => 1, 'url' => 'images/test.jpg', 'media_type' => 'image', 'alt_text' => 'Test', 'sort_order' => 0, 'is_primary' => 0],
            ]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test', $output);
    }

    public function testHandleDeleteDelegatesToDao(): void
    {
        $this->dao->expects($this->once())
            ->method('getProductMedia')
            ->with('SKU001')
            ->willReturn([]);

        $this->tab->handleDelete('SKU001');
    }

    /**
     * Regression: POST upload must persist via the tab handler without a
     * header('Location') hard refresh (GitHub issue #11 / #24).
     */
    public function testPostUploadImageInvalidFileDoesNotAddMedia(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_media_upload' => 'Upload Image', 'stock_id' => 'SKU001'];
        $_FILES['media_file'] = [
            'error'    => UPLOAD_ERR_NO_FILE,
            'size'     => 0,
            'name'     => '',
            'type'     => '',
            'tmp_name' => '',
        ];

        $this->dao->expects($this->never())
            ->method('addMedia');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $this->assertContains('File upload failed or empty.', $GLOBALS['test_errors'] ?? []);
        $GLOBALS['test_errors'] = [];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST, $_FILES);
    }

    /**
     * Regression: POST delete must delete via the tab handler without a
     * header('Location') hard refresh (GitHub issue #11 / #24).
     */
    public function testPostDeleteMediaItemDeletesWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_media_delete' => '1'];

        $this->dao->expects($this->once())
            ->method('getMediaItem')
            ->with(1)
            ->willReturn(['id' => 1, 'stock_id' => 'SKU001', 'url' => 'images/test.jpg']);
        $this->dao->expects($this->once())
            ->method('deleteMedia')
            ->with(1);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    public function testPostDeleteMediaItemSkipsForeignStockId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_media_delete' => '2'];

        $this->dao->expects($this->once())
            ->method('getMediaItem')
            ->with(2)
            ->willReturn(['id' => 2, 'stock_id' => 'OTHER', 'url' => 'images/test.jpg']);
        $this->dao->expects($this->never())
            ->method('deleteMedia');

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
