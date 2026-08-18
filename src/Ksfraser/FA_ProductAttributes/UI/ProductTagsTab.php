<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;

/**
 * Single Responsibility: Renders the Product Tags admin tab.
 *
 * Two modes:
 * - Global management only  ($stockId === ''): list + add + delete tags
 * - Per-product assignment   ($stockId !== ''): assignment section first,
 *   followed by the global management section
 *
 * Tags are short labels (e.g. "on-sale", "gift-idea", "bundle-included")
 * that complement category assignments for filtering and storefront badging.
 */
class ProductTagsTab
{
    /** @var ProductTagsDao */
    private $dao;

    public function __construct(ProductTagsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Render the tags tab.
     *
     * @param string $stockId  Empty when managing tags globally; non-empty for per-product view.
     */
    public function render(string $stockId = ''): void
    {
        if ($stockId !== '') {
            $this->renderAssignmentSection($stockId);
        }

        $this->renderGlobalManagementSection();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Per-product assignment
    // ─────────────────────────────────────────────────────────────────────────

    private function renderAssignmentSection(string $stockId): void
    {
        $allTags     = $this->dao->listTags();
        $assigned    = $this->dao->getProductTags($stockId);
        $assignedIds = [];
        foreach ($assigned as $t) {
            $assignedIds[(int)$t['id']] = true;
        }

        echo '<fieldset><legend>' . _('Assigned Tags') . '</legend>';

        if (empty($allTags)) {
            echo '<p>' . _('No tags defined yet. Create tags below.') . '</p>';
        } else {
            echo '<p>' . _('Check the tags that apply to this product.') . '</p>';
            foreach ($allTags as $tag) {
                $tagId   = (int)$tag['id'];
                $name    = htmlspecialchars((string)$tag['name']);
                $checked = isset($assignedIds[$tagId]) ? ' checked' : '';

                // Each checkbox posts as add_tag_assignment or remove_tag_assignment
                // via inline JS — simpler: we submit individual forms per tag change.
                // UX: auto-submit on change for immediate feedback.
                $addJs = 'this.form.elements[\'action\'].value=this.checked'
                    . '?\'add_tag_assignment\':\'remove_tag_assignment\';'
                    . 'this.form.submit();';

                echo '<span style="margin-right:1.5em;">';
                echo '<form method="post" action="" style="display:inline">';
                echo '<input type="hidden" name="action"   value="add_tag_assignment">';
                echo '<input type="hidden" name="tag_id"   value="' . $tagId . '">';
                echo '<label>';
                echo '<input type="checkbox" onchange="' . htmlspecialchars($addJs) . '"' . $checked . '> ';
                echo $name;
                echo '</label>';
                echo '</form>';
                echo '</span>';
            }
        }

        echo '</fieldset>';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Global tag management
    // ─────────────────────────────────────────────────────────────────────────

    private function renderGlobalManagementSection(): void
    {
        echo '<fieldset><legend>' . _('Manage Tags') . '</legend>';

        $tags = $this->dao->listTags();

        if (!empty($tags)) {
            echo '<table class="tablestyle">';
            echo '<thead><tr>';
            echo '<th>' . _('Name') . '</th>';
            echo '<th>' . _('Slug') . '</th>';
            echo '<th>' . _('Actions') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($tags as $tag) {
                $tagId = (int)$tag['id'];
                $name  = htmlspecialchars((string)$tag['name']);
                $slug  = htmlspecialchars((string)$tag['slug']);

                echo '<tr>';
                echo '<td>' . $name . '</td>';
                echo '<td><code>' . $slug . '</code></td>';
                echo '<td>';
                // Delete form
                echo '<form method="post" action="" style="display:inline">';
                echo '<input type="hidden" name="action" value="delete_tag">';
                echo '<input type="hidden" name="tag_id" value="' . $tagId . '">';
                echo '<input type="submit" value="' . _('Delete') . '" '
                    . 'onclick="return confirm(\'' . _('Delete this tag and all its assignments?') . '\')">';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . _('No tags defined yet.') . '</p>';
        }

        // Add new tag
        echo '<h4>' . _('Add New Tag') . '</h4>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"  value="upsert_tag">';
        echo '<input type="hidden" name="tag_id"  value="0">';
        echo '<table class="tablestyle_noborder">';
        echo '<tr>';
        echo '<td><label for="new_tag_name">' . _('Name') . '</label></td>';
        echo '<td><input type="text" id="new_tag_name" name="name" required maxlength="128" '
            . 'class="form-control" placeholder="e.g. on-sale"></td>';
        echo '</tr><tr>';
        echo '<td><label for="new_tag_slug">' . _('Slug') . '</label></td>';
        echo '<td><input type="text" id="new_tag_slug" name="slug" maxlength="128" '
            . 'class="form-control" placeholder="' . _('Auto-generated if blank') . '"></td>';
        echo '</tr>';
        echo '</table>';
        echo '<p><input type="submit" value="' . _('Add Tag') . '"></p>';
        echo '</form>';
        echo '</fieldset>';
    }
}
