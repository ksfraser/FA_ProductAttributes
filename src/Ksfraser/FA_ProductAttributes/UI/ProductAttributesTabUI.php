<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Generate HTML for the Product Attributes tab
 * (product hierarchy parent selector and current assignments table).
 */
class ProductAttributesTabUI
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Render the main tab content for the given product.
     *
     * @param string $stock_id The product's stock_id
     * @return string HTML output
     */
    public function renderMainTab(string $stock_id): string
    {
        $assignments = $this->dao->listAssignments($stock_id);
        $categoryAssignments = $this->dao->listCategoryAssignments($stock_id);
        $currentParent = $this->dao->getProductParent($stock_id);
        $allProducts = $this->dao->getAllProducts();

        global $path_to_root;
        $adminUrl = $path_to_root . '/modules/FA_ProductAttributes/public/index.php';

        $html = "<p><small>" . _('Manage attribute categories, values, and assignments on the')
            . " <a href=\"" . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . "\">"
            . _('Product Attributes admin page') . "</a>.</small></p>";

        $html .= "<h4>" . _('Product Hierarchy') . ":</h4>";

        // Parent selector
        $html .= "<label>" . _('Parent Product') . ": <select name='parent_stock_id'>";
        $html .= "<option value=''>None</option>";
        foreach ($allProducts as $product) {
            if ($product['stock_id'] === $stock_id) {
                continue;
            }
            $selected = ($currentParent === $product['stock_id']) ? 'selected' : '';
            $html .= "<option value='" . htmlspecialchars($product['stock_id'], ENT_QUOTES, 'UTF-8') . "' "
                . $selected . ">"
                . htmlspecialchars($product['stock_id'] . ' - ' . $product['description'], ENT_QUOTES, 'UTF-8')
                . "</option>";
        }
        $html .= "</select></label> ";

        $html .= "<h4>" . _('Category Assignments') . ":</h4>";
        if (empty($categoryAssignments)) {
            $html .= "<p>" . _('No attribute categories assigned to this product.') . "</p>";
        } else {
            $html .= "<table class='tablestyle2'>";
            $html .= "<tr><th>" . _('Category') . "</th><th>" . _('Actions') . "</th></tr>";
            foreach ($categoryAssignments as $ca) {
                $label = htmlspecialchars($ca['label'] ?? $ca['code'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= "<tr>";
                $html .= "<td>" . $label . "</td>";
                $html .= "<td>" . _('Assigned') . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
        }

        $html .= "<h4>" . _('Attribute Assignments') . ":</h4>";
        if (empty($assignments)) {
            $html .= "<p>" . _('No attribute values assigned to this product.') . "</p>";
        } else {
            $html .= "<table class='tablestyle2'>";
            $html .= "<tr><th>" . _('Category') . "</th><th>" . _('Value') . "</th></tr>";
            foreach ($assignments as $assignment) {
                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($assignment['category_label'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
                $html .= "<td>" . htmlspecialchars($assignment['value_label'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
        }

        return $html;
    }
}
