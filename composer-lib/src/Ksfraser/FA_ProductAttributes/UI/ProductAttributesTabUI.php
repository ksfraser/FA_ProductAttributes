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

        $html .= "<button type='button' onclick='fa_pa_updateParent(this)' name='update_product_config' value='1'>Update</button>";
        $html .= "</form>";

        $html .= "<script>
        function fa_pa_updateParent(button) {
            var form = button.form;
            var formData = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href + '&ajax=1', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert(response.message);
                            } else {
                                alert('Error: ' + response.message);
                            }
                        } catch (e) {
                            alert('Invalid response from server: ' + xhr.responseText.substring(0, 100));
                        }
                    } else {
                        alert('Error updating parent product: ' + xhr.status);
                    }
                }
            };
            xhr.send(formData);
        }
        </script>";

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