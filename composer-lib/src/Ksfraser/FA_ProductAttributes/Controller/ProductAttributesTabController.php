<?php

namespace Ksfraser\FA_ProductAttributes\Controller;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Controller for Product Attributes tab
 * Handles form submissions and Ajax requests
 */
class ProductAttributesTabController
{
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Handle Ajax requests
     */
    public function handleAjax()
    {
        $response = ['success' => false, 'message' => 'Unknown error'];

        if (isset($_POST['update_product_config'])) {
            $parentStockId = $_POST['parent_stock_id'] ?? null;
            if ($parentStockId === '') $parentStockId = null;
            try {
                $this->dao->setProductParent($_POST['stock_id'], $parentStockId);
                $response = ['success' => true, 'message' => 'Product configuration updated.'];
            } catch (\Exception $e) {
                $response = ['success' => false, 'message' => 'Failed to update product configuration: ' . $e->getMessage()];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * Handle normal POST requests
     */
    public function handlePost($stock_id)
    {
        if (isset($_POST['update_product_config'])) {
            $parentStockId = $_POST['parent_stock_id'] ?? null;
            if ($parentStockId === '') $parentStockId = null;
            try {
                $this->dao->setProductParent($stock_id, $parentStockId);
                display_notification("Product configuration updated.");
            } catch (\Exception $e) {
                display_error("Failed to update product configuration: " . $e->getMessage());
            }
        }
    }
}