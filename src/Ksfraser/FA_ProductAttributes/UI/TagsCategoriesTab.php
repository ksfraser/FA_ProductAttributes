<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;

/**
 * Single Responsibility: Renders the combined Tags + Categories admin tab.
 *
 * Layout:
 *   1. Product Attribute Categories (DDL + table of assigned categories)
 *   2. <hr> separator
 *   3. All known tags as checkboxes
 *
 * Business rule: When a category is assigned to a product, a tag with the
 * same name is auto-created (if needed) and assigned. Removing the category
 * does NOT remove the tag.
 */
class TagsCategoriesTab
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

    /**
     * Render the combined tab content.
     *
     * All controls live directly inside the item form (no nested <form> tags)
     * so a submit retains the selected product and the current tab.
     */
    public function render(string $stockId): void
    {
        echo '<input type="hidden" name="action"    value="save_tags_categories">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';

        $this->renderCategoriesSection($stockId);

        echo '<hr>';

        $this->renderTagsSection($stockId);

        echo '<p><input type="submit" name="pa_tags_save" value="' . _('Save') . '"></p>';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Categories section (top)
    // ─────────────────────────────────────────────────────────────────────────

    private function renderCategoriesSection(string $stockId): void
    {
        $allCategories = $this->attributesDao->listCategories();
        $assigned      = $stockId !== '' ? $this->attributesDao->listCategoryAssignments($stockId) : [];
        $assignedIds   = [];
        foreach ($assigned as $cat) {
            $assignedIds[(int)$cat['id']] = true;
        }

        echo '<fieldset><legend>' . _('Product Attribute Categories') . '</legend>';

        // Table of currently assigned categories
        echo '<table class="tablestyle">';
        echo '<thead><tr>';
        echo '<th>' . _('Code') . '</th>';
        echo '<th>' . _('Label') . '</th>';
        echo '<th>' . _('Actions') . '</th>';
        echo '</tr></thead><tbody>';

        if (!empty($assigned)) {
            foreach ($assigned as $cat) {
                $catId = (int)($cat['id'] ?? 0);
                $code  = htmlspecialchars((string)($cat['code'] ?? ''));
                $label = htmlspecialchars((string)($cat['label'] ?? ''));

                echo '<tr>';
                echo '<td><code>' . $code . '</code></td>';
                echo '<td>' . $label . '</td>';
                echo '<td><button type="submit" name="pa_category_remove" value="' . $catId . '" '
                    . 'style="color:red" formnovalidate '
                    . 'onclick="return confirm(\'' . _('Remove this category assignment?') . '\')">'
                    . _('Remove') . '</button></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3"><em>' . _('No categories assigned yet.') . '</em></td></tr>';
        }
        echo '</tbody></table>';

        // DDL + Add button
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('Category') . '</td><td>';
        echo '<select name="pa_category_id">';
        echo '<option value="0">' . _('-- Select Category --') . '</option>';
        foreach ($allCategories as $cat) {
            $catId  = (int)($cat['id'] ?? 0);
            $code   = htmlspecialchars((string)($cat['code'] ?? ''));
            $label  = htmlspecialchars((string)($cat['label'] ?? ''));
            $sel    = isset($assignedIds[$catId]) ? ' disabled' : '';
            $mark   = isset($assignedIds[$catId]) ? ' [' . _('assigned') . ']' : '';
            echo '<option value="' . $catId . '"' . $sel . '>' . $code . ' - ' . $label . $mark . '</option>';
        }
        echo '</select></td>';
        echo '<td><input type="submit" name="pa_category_add" value="' . _('Add Category') . '" formnovalidate></td>';
        echo '</tr></table>';

        echo '</fieldset>';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tags section (bottom)
    // ─────────────────────────────────────────────────────────────────────────

    private function renderTagsSection(string $stockId): void
    {
        $allTags      = $this->tagsDao->listTags();
        $assigned     = ($stockId !== '') ? $this->tagsDao->getProductTags($stockId) : [];
        $assignedIds  = [];
        foreach ($assigned as $t) {
            $assignedIds[(int)$t['id']] = true;
        }

        echo '<fieldset><legend>' . _('Product Tags') . '</legend>';

        if (empty($allTags)) {
            echo '<p>' . _('No tags defined yet. Create them via global tag management.') . '</p>';
        } else {
            echo '<p>' . _('Check the tags that apply to this product.') . '</p>';
            echo '<table class="tablestyle_noborder">';
            foreach ($allTags as $tag) {
                $tagId   = (int)$tag['id'];
                $name    = htmlspecialchars((string)($tag['name'] ?? ''));
                $slug    = htmlspecialchars((string)($tag['slug'] ?? ''));
                $checked = isset($assignedIds[$tagId]) ? ' checked' : '';

                echo '<tr>';
                echo '<td><label for="tag_' . $tagId . '">';
                echo '<input type="checkbox" id="tag_' . $tagId . '" '
                    . 'name="product_tags[]" value="' . $tagId . '"' . $checked . '> ';
                echo $name;
                if ($slug !== $name) {
                    echo ' <small>(<code>' . $slug . '</code>)</small>';
                }
                echo '</label></td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        echo '</fieldset>';
    }
}
