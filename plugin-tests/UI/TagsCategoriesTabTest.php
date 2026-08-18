<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\UI\TagsCategoriesTab;
use PHPUnit\Framework\TestCase;

class TagsCategoriesTabTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $attributesDao;

    /** @var ProductTagsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $tagsDao;

    /** @var TagsCategoriesTab */
    private $tab;

    protected function setUp(): void
    {
        $this->attributesDao = $this->createMock(ProductAttributesDao::class);
        $this->tagsDao       = $this->createMock(ProductTagsDao::class);
        $this->tab           = new TagsCategoriesTab($this->attributesDao, $this->tagsDao);
    }

    public function testRenderShowsSaveButton(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('save_tags_categories', $output);
        $this->assertStringNotContainsString('name="stock_id"', $output);
        $this->assertStringContainsString('type="submit"', $output);
    }

    public function testRenderShowsCategoriesSection(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Color'],
            ['id' => 2, 'code' => 'SIZE', 'label' => 'Size'],
        ]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Product Attribute Categories', $output);
        $this->assertStringContainsString('COLOR', $output);
        $this->assertStringContainsString('SIZE', $output);
        $this->assertStringContainsString('No categories assigned yet', $output);
    }

    public function testRenderShowsAssignedCategoriesInTable(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Color'],
        ]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([
            ['id' => 1, 'category_id' => 1, 'code' => 'COLOR', 'label' => 'Color'],
        ]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('COLOR', $output);
        $this->assertStringContainsString('Remove', $output);
        $this->assertStringNotContainsString('No categories assigned yet', $output);
    }

    public function testRenderShowsColorOptionDisabledWhenAssigned(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Color'],
            ['id' => 2, 'code' => 'SIZE', 'label' => 'Size'],
        ]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([
            ['id' => 1, 'category_id' => 1],
        ]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('disabled', $output);
    }

    public function testRenderShowsTagsSectionWithCheckboxes(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
            ['id' => 2, 'name' => 'New', 'slug' => 'new'],
        ]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('Product Tags', $output);
        $this->assertStringContainsString('On Sale', $output);
        $this->assertStringContainsString('New', $output);
        $this->assertStringContainsString('checkbox', $output);
        $this->assertStringContainsString('tag_1', $output);
        $this->assertStringContainsString('tag_2', $output);
        $this->assertStringContainsString('on-sale', $output);
    }

    public function testRenderShowsCheckedTagsWhenAssigned(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
            ['id' => 2, 'name' => 'New', 'slug' => 'new'],
        ]);
        $this->tagsDao->method('getProductTags')->willReturn([
            ['id' => 1],
        ]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('checked', $output);
    }

    public function testRenderShowsNoTagsMessage(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('No tags defined yet', $output);
    }

    public function testRenderWithoutStockIdShowsEmptyAssignments(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->expects($this->never())
            ->method('listCategoryAssignments');
        $this->tagsDao->method('listTags')->willReturn([]);
        $this->tagsDao->expects($this->never())
            ->method('getProductTags');

        ob_start();
        $this->tab->render('');
        $output = ob_get_clean();

        $this->assertStringContainsString('save_tags_categories', $output);
    }

    public function testRenderDisplaysSlugOnlyWhenDifferentFromName(): void
    {
        $this->attributesDao->method('listCategories')->willReturn([]);
        $this->attributesDao->method('listCategoryAssignments')->willReturn([]);
        $this->tagsDao->method('listTags')->willReturn([
            ['id' => 1, 'name' => 'On Sale', 'slug' => 'on-sale'],
            ['id' => 2, 'name' => 'new', 'slug' => 'new'],
        ]);
        $this->tagsDao->method('getProductTags')->willReturn([]);

        ob_start();
        $this->tab->render('SKU001');
        $output = ob_get_clean();

        $this->assertStringContainsString('on-sale', $output);
    }
}
