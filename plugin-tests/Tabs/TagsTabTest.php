<?php

namespace Ksfraser\FA_ProductAttributes\Test\Tabs;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\Tabs\TagsTab;
use PHPUnit\Framework\TestCase;

class TagsTabTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $attributesDao;

    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $tagsDao;

    /** @var TagsTab */
    private $tab;

    protected function setUp(): void
    {
        $this->attributesDao = $this->createMock(ProductAttributesDao::class);
        $this->tagsDao       = $this->createMock(ProductTagsDao::class);
        $this->tab           = new TagsTab($this->attributesDao, $this->tagsDao);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
    }

    public function testGetName(): void
    {
        $this->assertSame('tags_categories', $this->tab->getName());
    }

    public function testGetTabKey(): void
    {
        $this->assertSame('tags_categories', $this->tab->getTabKey());
    }

    public function testGetTabLabel(): void
    {
        $this->assertSame('Tags', $this->tab->getTabLabel());
    }

    public function testRenderTabContentDelegatesToTagsCategoriesTab(): void
    {
        $this->attributesDao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('SKU001')
            ->willReturn([]);
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('save_tags_categories', $output);
        $this->assertStringNotContainsString('name="stock_id"', $output);
        $this->assertStringContainsString('pa_category_id', $output);
        $this->assertStringNotContainsString('name="category_id"', $output);
    }

    public function testHandleSaveDelegatesToTagSave(): void
    {
        $postData = ['product_tags' => ['1', '2', '3']];
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->expects($this->once())
            ->method('syncAssignments')
            ->with('SKU001', [1, 2, 3]);

        $this->tab->handleSave('SKU001', $postData);
    }

    /**
     * Regression (GitHub issues #22 / #23): a plain Save must NOT interpret the
     * pa_category_id DDL as a category assignment. Adding a category is only
     * performed by the dedicated pa_category_add button, otherwise a removed
     * category would silently be re-added on the next Save.
     */
    public function testHandleSaveDoesNotAutoAssignFromDdl(): void
    {
        $postData = [
            'pa_category_id' => '5',
            'product_tags' => ['1'],
        ];
        $this->attributesDao->expects($this->never())
            ->method('addCategoryAssignment');
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->expects($this->once())
            ->method('syncAssignments')
            ->with('SKU001', [1]);

        $this->tab->handleSave('SKU001', $postData);
    }

    public function testHandleSaveWithEmptyStockIdDoesNothing(): void
    {
        $this->tagsDao->expects($this->never())
            ->method('syncAssignments');

        $this->tab->handleSave('', ['product_tags' => ['1']]);
    }

    public function testHandleDeleteRemovesAllCategoryAndTagAssignments(): void
    {
        $this->attributesDao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('SKU001')
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
            ]);
        $this->attributesDao->expects($this->exactly(2))
            ->method('removeCategoryAssignment')
            ->withConsecutive(
                ['SKU001', 1],
                ['SKU001', 2]
            );

        $this->tagsDao->expects($this->once())
            ->method('getProductTags')
            ->with('SKU001')
            ->willReturn([
                ['id' => 10],
                ['id' => 20],
            ]);
        $this->tagsDao->expects($this->exactly(2))
            ->method('removeAssignment')
            ->withConsecutive(
                ['SKU001', 10],
                ['SKU001', 20]
            );

        $this->tab->handleDelete('SKU001');
    }

    public function testHandleDeleteWithNoAssignments(): void
    {
        $this->attributesDao->expects($this->once())
            ->method('listCategoryAssignments')
            ->willReturn([]);
        $this->attributesDao->expects($this->never())
            ->method('removeCategoryAssignment');
        $this->tagsDao->expects($this->once())
            ->method('getProductTags')
            ->willReturn([]);
        $this->tagsDao->expects($this->never())
            ->method('removeAssignment');

        $this->tab->handleDelete('SKU001');
    }

    /**
     * Verify that a plain Save does NOT auto-create/assign category-derived
     * tags — only the dedicated "Add Category" button should do that.
     * Unchecking a category-derived tag must actually remove it (issue #22).
     */
    public function testHandleSaveDoesNotAutoSyncCategoryTags(): void
    {
        $this->attributesDao->method('listCategoryAssignments')->willReturn([
            ['id' => 1, 'label' => 'Size'],
        ]);
        $this->attributesDao->method('listCategories')->willReturn([
            ['id' => 1, 'label' => 'Size'],
        ]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->expects($this->never())
            ->method('upsertTag');
        $this->tagsDao->expects($this->never())
            ->method('addAssignment');
        $this->tagsDao->expects($this->once())
            ->method('syncAssignments')
            ->with('SKU001', []);

        $this->tab->handleSave('SKU001', ['product_tags' => []]);
    }

    public function testHandleSaveWithEmptyProductTagsArray(): void
    {
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->expects($this->once())
            ->method('syncAssignments')
            ->with('SKU001', []);

        $this->tab->handleSave('SKU001', []);
    }

    public function testHandleSaveMultipleTagsSelected(): void
    {
        $postData = ['product_tags' => ['1', '5', '10']];
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->expects($this->once())
            ->method('syncAssignments')
            ->with('SKU001', [1, 5, 10]);

        $this->tab->handleSave('SKU001', $postData);
    }

    /**
     * Regression: adding a second category must work via the dedicated
     * Add Category button without a hard refresh (GitHub issue #23 / #24).
     */
    public function testPostAddCategoryAssignsWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_category_add' => 'Add Category', 'pa_category_id' => '5'];

        $this->attributesDao->expects($this->once())
            ->method('addCategoryAssignment')
            ->with('SKU001', 5);
        $this->attributesDao->method('listCategories')->willReturn([
            ['id' => 5, 'label' => 'Color'],
        ]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    /**
     * Regression: Remove must actually remove the category assignment
     * (GitHub issue #22 / #24).
     */
    public function testPostRemoveCategoryRemovesWithoutRedirect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['pa_category_remove' => '3'];

        $this->attributesDao->expects($this->once())
            ->method('removeCategoryAssignment')
            ->with('SKU001', 3);
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    /**
     * Regression: the Save button (pa_tags_save) must sync tag assignments
     * without a hard refresh (GitHub issue #24).
     */
    public function testPostTagsSaveSyncsAssignments(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'save_tags_categories', 'pa_tags_save' => 'Save', 'product_tags' => [1, 2]];

        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);
        $this->tagsDao->expects($this->once())
            ->method('syncAssignments')
            ->with('SKU001', [1, 2]);

        ob_start();
        $this->tab->renderTabContent('SKU001');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }

    /**
     * Regression: a main-form POST without the Save button (e.g. changing the
     * product selector) must NOT sync tag assignments (GitHub issues #16 / #28).
     */
    public function testPostWithoutSaveButtonDoesNotSyncTags(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['action' => 'save_tags_categories', 'product_tags' => [1, 2], 'stock_id' => 'SKU002'];

        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);
        $this->tagsDao->expects($this->never())
            ->method('syncAssignments');

        ob_start();
        $this->tab->renderTabContent('SKU002');
        ob_get_clean();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST);
    }
}
