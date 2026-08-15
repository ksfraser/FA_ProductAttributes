<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\UI\TagsCategoriesTab;

/**
 * Tab adapter for the combined Tags + Categories tab.
 *
 * Handles POST actions for both category assignments (with auto-tag sync)
 * and tag assignments, then delegates rendering to TagsCategoriesTab.
 */
class TagsTab extends AbstractTab
{
    /** @var ProductAttributesDao */
    private $attributesDao;

    /** @var ProductTagsDao */
    private $tagsDao;

    public function __construct(ProductAttributesDao $attributesDao, ProductTagsDao $tagsDao)
    {
        $this->attributesDao = $attributesDao;
        $this->tagsDao       = $tagsDao;
    }

    public function getName(): string
    {
        return 'tags_categories';
    }

    public function getTabKey(): string
    {
        return 'tags_categories';
    }

    public function getTabLabel(): string
    {
        return _('Tags');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $ui = new TagsCategoriesTab($this->attributesDao, $this->tagsDao);
        $ui->render($stockId);
    }

    public function handleSave(string $stockId, array $postData): void
    {
        $this->handleTagSave($stockId, $postData);
    }

    public function handleDelete(string $stockId): void
    {
        // Remove all category assignments
        $cats = $this->attributesDao->listCategoryAssignments($stockId);
        foreach ($cats as $cat) {
            $this->attributesDao->removeCategoryAssignment($stockId, (int)$cat['id']);
        }
        // Remove all tag assignments
        $tags = $this->tagsDao->getProductTags($stockId);
        foreach ($tags as $tag) {
            $this->tagsDao->removeAssignment($stockId, (int)$tag['id']);
        }
    }

    /**
     * Handle inline POST actions from the tab (category add/remove, tag add/remove).
     *
     * Actions arrive as named submit buttons within the item form so that the
     * currently selected product and tab are retained (no hard refresh).
     */
    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }

        if (isset($_POST['pa_category_add'])) {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            if ($categoryId > 0) {
                $this->attributesDao->addCategoryAssignment($stockId, $categoryId);
                $this->autoSyncCategoryTag($stockId, $categoryId, true);
            }
            if (function_exists('display_notification')) {
                display_notification(_('Category assigned.'));
            }
            return;
        }

        if (isset($_POST['pa_category_remove'])) {
            $categoryId = (int)($_POST['pa_category_remove'] ?? 0);
            if ($categoryId > 0) {
                $this->attributesDao->removeCategoryAssignment($stockId, $categoryId);
                // Business rule: do NOT auto-remove the tag
            }
            if (function_exists('display_notification')) {
                display_notification(_('Category assignment removed.'));
            }
            return;
        }

        if (isset($_POST['pa_tags_save'])) {
            $this->handleTagSave($stockId, $_POST);
        }
    }

    /**
     * Handle tag checkbox saves from the main form submission.
     */
    private function handleTagSave(string $stockId, array $postData): void
    {
        if ($stockId === '') {
            return;
        }

        // Handle category assignment if DDL was submitted
        if (isset($postData['category_id']) && (int)$postData['category_id'] > 0) {
            $categoryId = (int)$postData['category_id'];
            $this->attributesDao->addCategoryAssignment($stockId, $categoryId);
            $this->autoSyncCategoryTag($stockId, $categoryId, true);
        }

        // Sync tag checkboxes
        $tagIds = [];
        if (isset($postData['product_tags']) && is_array($postData['product_tags'])) {
            $tagIds = array_map('intval', $postData['product_tags']);
        }
        $this->tagsDao->syncAssignments($stockId, $tagIds);
    }

    /**
     * When a category is assigned, auto-create a tag with the same name if needed,
     * then assign it. When a category is removed, the tag stays (no auto-remove).
     *
     * @param string $stockId
     * @param int    $categoryId
     * @param bool   $assign  true = assign tag, false = no-op (removal does nothing)
     */
    private function autoSyncCategoryTag(string $stockId, int $categoryId, bool $assign): void
    {
        if (!$assign) {
            return;
        }

        $allCats = $this->attributesDao->listCategories();
        $catName = '';
        foreach ($allCats as $c) {
            if ((int)($c['id'] ?? 0) === $categoryId) {
                $catName = trim((string)($c['label'] ?? $c['code'] ?? ''));
                break;
            }
        }
        if ($catName === '') {
            return;
        }

        // Find or create a tag matching this category name
        $allTags  = $this->tagsDao->listTags();
        $tagId    = 0;
        $slug     = strtolower(str_replace([' ', '/', '&'], '-', $catName));
        $slug     = preg_replace('/-+/', '-', $slug);
        $slug     = trim($slug, '-');

        foreach ($allTags as $t) {
            if (strtolower((string)($t['name'] ?? '')) === strtolower($catName)) {
                $tagId = (int)$t['id'];
                break;
            }
        }

        if ($tagId === 0) {
            $this->tagsDao->upsertTag($catName, $slug);
            // Fetch the newly created tag
            $allTags = $this->tagsDao->listTags();
            foreach ($allTags as $t) {
                if (strtolower((string)($t['name'] ?? '')) === strtolower($catName)) {
                    $tagId = (int)$t['id'];
                    break;
                }
            }
        }

        if ($tagId > 0) {
            $this->tagsDao->addAssignment($stockId, $tagId);
        }
    }
}
