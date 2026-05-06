<?php

namespace Ksfraser\FA_ProductAttributes\Controller;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Handle form submissions and Ajax requests for the
 * Product Attributes tab (product hierarchy / parent-product config).
 */
class ProductAttributesTabController
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Handle Ajax requests (returns JSON, exits).
     */
    public function handleAjax(): void
    {
        $response = ['success' => false, 'message' => 'Unknown error'];

        if (isset($_POST['update_product_config'])) {
            $parentStockId = $_POST['parent_stock_id'] ?? null;
            if ($parentStockId === '') {
                $parentStockId = null;
            }
            try {
                $this->dao->setProductParent((string)$_POST['stock_id'], $parentStockId);
                $response = ['success' => true, 'message' => 'Product configuration updated.'];
            } catch (\Exception $e) {
                $response = ['success' => false, 'message' => 'Failed to update product configuration: ' . $e->getMessage()];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * Handle normal (non-Ajax) POST requests.
     *
     * @param string $stock_id The stock_id of the current product
     */
    public function handlePost(string $stock_id): void
    {
        if (isset($_POST['update_product_config'])) {
            $parentStockId = $_POST['parent_stock_id'] ?? null;
            if ($parentStockId === '') {
                $parentStockId = null;
            }
            try {
                $this->dao->setProductParent($stock_id, $parentStockId);
                display_notification('Product configuration updated.');
            } catch (\Exception $e) {
                display_error('Failed to update product configuration: ' . $e->getMessage());
            }
        }
    }
}
