<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\ProductTagsTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use PHPUnit\Framework\TestCase;

class ProductTagsTabTest extends TestCase
{
    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var ProductTagsTab */
    private $tab;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProductTagsDao::class);
        $this->tab = new ProductTagsTab($this->dao);
    }

    public function testRenderGlobalViewShowsTagListAndAddForm(): void
    {
        $this->dao->method('listTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
        ]);

        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringContainsString('On Sale', $html);
        $this->assertStringContainsString('value="upsert_tag"', $html);
    }

    public function testRenderGlobalViewShowsNoTagsMessage(): void
    {
        $this->dao->method('listTags')->willReturn([]);

        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringContainsString('No tags defined', $html);
    }

    public function testRenderWithStockIdShowsAssignmentSection(): void
    {
        $this->dao->method('listTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
        ]);
        $this->dao->method('getProductTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        // Should show assignment section (checkboxes per tag)
        $this->assertStringContainsString('add_tag_assignment', $html);
        $this->assertStringContainsString('On Sale', $html);
    }

    public function testRenderWithStockIdShowsCheckedCheckboxForAssignedTag(): void
    {
        $this->dao->method('listTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
        ]);
        $this->dao->method('getProductTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale'],
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        $this->assertStringContainsString('checked', $html);
    }

    public function testRenderWithStockIdAlsoShowsGlobalManagement(): void
    {
        $this->dao->method('listTags')->willReturn([]);
        $this->dao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $html = ob_get_clean();

        // Global management Add Tag form should still appear
        $this->assertStringContainsString('value="upsert_tag"', $html);
    }

    public function testRenderDeleteFormRequiresConfirmation(): void
    {
        $this->dao->method('listTags')->willReturn([
            ['id' => 2, 'name' => 'New', 'slug' => 'new'],
        ]);

        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringContainsString('value="delete_tag"', $html);
        $this->assertStringContainsString('confirm(', $html);
    }

    public function testRenderEscapesTagNameInOutput(): void
    {
        $this->dao->method('listTags')->willReturn([
            ['id' => 1, 'name' => '<b>Bad</b>', 'slug' => 'bad'],
        ]);

        ob_start();
        $this->tab->render('');
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<b>Bad</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }
}
