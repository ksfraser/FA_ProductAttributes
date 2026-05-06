<?php

namespace Ksfraser\FA_ProductAttributes\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Generate HTML for the Product Attributes tab
 */
class ProductAttributesTabUI
{
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Render the main tab content
     */
    public function renderMainTab($stock_id)
    {
        $assignments = $this->dao->listAssignments($stock_id);
        $categoryAssignments = $this->dao->listCategoryAssignments($stock_id);
        $isParent = !empty($categoryAssignments);
        $currentParent = $this->dao->getProductParent($stock_id);
        $allProducts = $this->dao->getAllProducts();

        $html = "<h4>Product Hierarchy:</h4>";
        $html .= "<form method='post' action='' target='_self' style='display: inline;'>";
        $html .= "<input type='hidden' name='stock_id' value='" . htmlspecialchars($stock_id) . "'>";

        // Parent selector
        $html .= "<label>Parent Product: <select name='parent_stock_id'>";
        $html .= "<option value=''>None</option>";
        foreach ($allProducts as $product) {
            if ($product['stock_id'] === $stock_id) continue;
            $selected = ($currentParent === $product['stock_id']) ? 'selected' : '';
            $html .= "<option value='" . htmlspecialchars($product['stock_id']) . "' $selected>" . htmlspecialchars($product['stock_id'] . ' - ' . $product['description']) . "</option>";
        }
        $html .= "</select></label> ";

        $html .= "<input type='submit' name='update_product_config' value='Update'>";
        $html .= "</form>";

        $html .= "<h4>Current Assignments:</h4>";
        if (empty($assignments)) {
            $html .= "<p>No attributes assigned to this product.</p>";
        } else {
            $html .= "<table class='tablestyle2'>";
            $html .= "<tr><th>Category</th><th>Value</th><th>Actions</th></tr>";
            foreach ($assignments as $assignment) {
                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($assignment['category_label'] ?? '') . "</td>";
                $html .= "<td>" . htmlspecialchars($assignment['value_label'] ?? '') . "</td>";
                $html .= "<td><a href='#'>Edit</a> | <a href='#'>Remove</a></td>";
                $html .= "</tr>";
            }
            $html .= "</table>";
        }

        return $html;
    }
}