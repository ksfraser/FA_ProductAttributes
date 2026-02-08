<?php

// FrontAccounting hooks file for FA_ProductAttributes_Categories plugin.
// This plugin extends the core FA_ProductAttributes module with category functionality.

define('SS_FA_ProductAttributes_Categories', 114 << 8);

class hooks_FA_ProductAttributes_Categories extends hooks
{
    var $module_name = 'FA_ProductAttributes_Categories';

    function install()
    {
        global $path_to_root;

        // Check if FA_ProductAttributes core module is installed
        $coreModulePath = $path_to_root . '/modules/FA_ProductAttributes';
        if (!file_exists($coreModulePath . '/hooks.php')) {
            display_error('FA_ProductAttributes core module must be installed before FA_ProductAttributes_Categories. Please install FA_ProductAttributes first.');
            return false;
        }

        // Plugin initialization is handled by the core module's plugin loader
        // No additional installation steps needed

        return true;
    }

    function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'stock':
                $app->add_rapp_function(
                    2,
                    _('Product Categories'),
                    $path_to_root . '/modules/FA_ProductAttributes_Categories/product_categories_admin.php',
                    'SA_PRODUCTATTRIBUTES_CATEGORIES'
                );
                break;
        }
    }

    function install_access()
    {
        $security_sections[SS_FA_ProductAttributes_Categories] = _("Product Attributes Categories");
        $security_areas['SA_PRODUCTATTRIBUTES_CATEGORIES'] = array(SS_FA_ProductAttributes_Categories | 101, _("Product Attributes Categories"));
        return array($security_areas, $security_sections);
    }

    /**
     * Register hooks for the categories plugin
     * Called by the core module's plugin loader on every page load
     */
    function register_hooks() {
        global $path_to_root;

        // Verify core module is available
        $coreModulePath = $path_to_root . '/modules/FA_ProductAttributes';
        if (!file_exists($coreModulePath . '/hooks.php')) {
            error_log('FA_ProductAttributes_Categories: Core module not found, skipping hook registration');
            return;
        }

        // Include the global hook manager
        require_once $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';

        // Get the hook manager
        $hooks = fa_hooks();

        // Register categories-specific hooks that extend the core attributes hooks
        $hooks->registerExtension('attributes_tab_content', 'product_attributes_categories', [$this, 'static_extend_attributes_tab'], 10);
        $hooks->registerExtension('attributes_save', 'product_attributes_categories', [$this, 'static_handle_categories_save'], 10);
        $hooks->registerExtension('attributes_delete', 'product_attributes_categories', [$this, 'static_handle_categories_delete'], 10);

        // Register hooks for the assignments tab extensions
        $hooks->add_hook('fa_product_attributes_assignments_buttons', [$this, 'add_categories_buttons'], 10);
        $hooks->add_hook('fa_product_attributes_assignments_after_table', [$this, 'add_categories_content'], 10);

        // Register hook for handling actions in admin interface
        $hooks->add_hook('fa_product_attributes_handle_action', [$this, 'handleAdminAction'], 10);

        // Register hooks for category management and bulk operations
        $hooks->add_hook('fa_product_attributes_categories_management_apply', [$this, 'applyCategoryManagement'], 10);
        $hooks->add_hook('fa_product_attributes_bulk_operations_register', [$this, 'registerBulkOperations'], 10);
    }

    /**
     * Hook callback to add categories buttons to the assignments tab
     */
    public function add_categories_buttons($params) {
        $stock_id = $params['stock_id'] ?? '';

        if (empty($stock_id)) {
            return [];
        }

        // Return HTML for category management buttons
        return [
            'categories' => [
                'label' => _('Manage Categories'),
                'url' => '#',
                'class' => 'btn btn-default',
                'onclick' => 'showCategoriesDialog("' . $stock_id . '"); return false;'
            ]
        ];
    }

    /**
     * Hook callback to add categories content after the assignments table
     */
    public function add_categories_content($params) {
        $stock_id = $params['stock_id'] ?? '';

        if (empty($stock_id)) {
            return '';
        }

        // Return HTML for categories management interface
        return '
        <div id="categories-management-section" style="margin-top: 20px; display: none;">
            <h4>' . _('Product Categories') . '</h4>
            <div id="categories-content">
                <!-- Categories content will be loaded here -->
            </div>
        </div>
        <script>
        function showCategoriesDialog(stockId) {
            $("#categories-management-section").toggle();
            if ($("#categories-management-section").is(":visible")) {
                loadCategoriesContent(stockId);
            }
        }

        function loadCategoriesContent(stockId) {
            $.ajax({
                url: "modules/FA_ProductAttributes/product_attributes_admin.php",
                data: { action: "load_categories", stock_id: stockId },
                type: "POST",
                success: function(response) {
                    $("#categories-content").html(response);
                },
                error: function() {
                    $("#categories-content").html("<p>Error loading categories content.</p>");
                }
            });
        }
        </script>';
    }

    /**
     * Handle admin actions for categories
     */
    public function handleAdminAction($params) {
        $action = $params['action'] ?? '';
        $data = $params['data'] ?? [];

        switch ($action) {
            case 'load_categories':
                return $this->loadCategoriesContent($data);
            case 'save_category_assignment':
                return $this->saveCategoryAssignment($data);
            case 'delete_category_assignment':
                return $this->deleteCategoryAssignment($data);
            default:
                return false;
        }
    }

    /**
     * Load categories content for a product
     */
    private function loadCategoriesContent($data) {
        $stock_id = $data['stock_id'] ?? '';

        if (empty($stock_id)) {
            return '<p>Invalid product ID.</p>';
        }

        // TODO: Implement categories loading logic
        // This would typically query the database for categories assigned to this product

        return '<p>Categories management interface for product: ' . htmlspecialchars($stock_id) . '</p>
               <p><em>Categories functionality will be implemented here.</em></p>';
    }

    /**
     * Save category assignment
     */
    private function saveCategoryAssignment($data) {
        // TODO: Implement category assignment saving
        return ['success' => true, 'message' => 'Category assignment saved (placeholder)'];
    }

    /**
     * Delete category assignment
     */
    private function deleteCategoryAssignment($data) {
        // TODO: Implement category assignment deletion
        return ['success' => true, 'message' => 'Category assignment deleted (placeholder)'];
    }

    /**
     * Apply category management rules
     */
    public function applyCategoryManagement($params) {
        // TODO: Implement category management logic
        return true;
    }

    /**
     * Register bulk operations for categories
     */
    public function registerBulkOperations($params) {
        // TODO: Register bulk category operations
        return [
            'assign_category' => [
                'label' => _('Assign Category'),
                'handler' => [$this, 'bulkAssignCategory']
            ],
            'remove_category' => [
                'label' => _('Remove Category'),
                'handler' => [$this, 'bulkRemoveCategory']
            ]
        ];
    }

    /**
     * Bulk assign category
     */
    public function bulkAssignCategory($params) {
        // TODO: Implement bulk category assignment
        return ['success' => true, 'message' => 'Bulk category assignment completed (placeholder)'];
    }

    /**
     * Bulk remove category
     */
    public function bulkRemoveCategory($params) {
        // TODO: Implement bulk category removal
        return ['success' => true, 'message' => 'Bulk category removal completed (placeholder)'];
    }

    /**
     * Static method to extend attributes tab content
     */
    public static function static_extend_attributes_tab($params) {
        $instance = new self();
        return $instance->extend_attributes_tab($params);
    }

    /**
     * Extend the attributes tab with categories content
     */
    public function extend_attributes_tab($params) {
        $stock_id = $params['stock_id'] ?? '';

        if (empty($stock_id)) {
            return '';
        }

        // Return HTML for categories section in attributes tab
        return '
        <div class="tab-section" id="categories-section">
            <h4>' . _('Product Categories') . '</h4>
            <p>Categories management will be integrated here.</p>
            <!-- TODO: Implement categories UI -->
        </div>';
    }

    /**
     * Static method to handle categories save
     */
    public static function static_handle_categories_save($params) {
        $instance = new self();
        return $instance->handle_categories_save($params);
    }

    /**
     * Handle saving categories data
     */
    public function handle_categories_save($params) {
        // TODO: Implement categories save logic
        return true;
    }

    /**
     * Static method to handle categories delete
     */
    public static function static_handle_categories_delete($params) {
        $instance = new self();
        return $instance->handle_categories_delete($params);
    }

    /**
     * Handle deleting categories data
     */
    public function handle_categories_delete($params) {
        // TODO: Implement categories delete logic
        return true;
    }
}