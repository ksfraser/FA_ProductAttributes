<?php

// FrontAccounting hooks file for the module.
// When installed under FA as modules/FA_ProductAttributes, this adds the admin page.

define('SS_FA_ProductAttributes', 112 << 8);

class hooks_FA_ProductAttributes extends hooks
{
    var $module_name = 'Product Attributes';

    function install()
    {
        global $path_to_root;

        // Install composer dependencies
        $module_path = $path_to_root . '/modules/FA_ProductAttributes';
        $result = $this->installComposerDependencies($module_path);

        if (!$result['success']) {
            // Log the error but don't fail the installation
            error_log('FA_ProductAttributes: ' . $result['message']);
            if (!empty($result['output'])) {
                error_log('Composer output: ' . $result['output']);
            }
        }

        // Create database schema programmatically as backup
        try {
            $this->createDatabaseSchema($module_path);
        } catch (Exception $e) {
            error_log('FA_ProductAttributes: Failed to create database schema: ' . $e->getMessage());
            // Don't fail installation if schema creation fails
        }

        // Register hooks for the module
        $this->register_hooks();

        return true; // Standard FA install return
    }

    function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'stock':
                $app->add_rapp_function(
                    2,
                    _('Product Attributes'),
                    $path_to_root . '/modules/FA_ProductAttributes/product_attributes_admin.php',
                    'SA_PRODUCTATTRIBUTES'
                );
                break;
        }
    }

    function install_access()
    {
        $security_sections[SS_FA_ProductAttributes] = _("Product Attributes");
        $security_areas['SA_PRODUCTATTRIBUTES'] = array(SS_FA_ProductAttributes | 101, _("Product Attributes"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        global $db_connections, $path_to_root;

        // Set TB_PREF for correct table prefix
        define('TB_PREF', $company . '_');

        // Ensure database schema exists (programmatic creation as backup)
        if (!$check_only) {
            try {
                $module_path = $path_to_root . '/modules/FA_ProductAttributes';
                $this->createDatabaseSchema($module_path);
            } catch (Exception $e) {
                error_log('FA_ProductAttributes: Failed to create database schema on activation: ' . $e->getMessage());
            }
        }

        $updates = array(
            'schema.sql' => array('product_attribute_categories', 'product_attribute_values', 'product_attribute_assignments')
        );

        return $this->update_databases($company, $updates, $check_only);
    }

    /**
     * Register hooks for the module
     * Called during module initialization
     */
    function register_hooks() {
        global $path_to_root;

        // Include the global hook manager
        require_once $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';

        // Get the hook manager
        $hooks = fa_hooks();

        // Register Product Attributes hooks for items.php
        $hooks->add_hook('item_display_tabs', [$this, 'add_product_attributes_tab'], 10);
        $hooks->add_hook('pre_item_write', [$this, 'handle_product_attributes_save'], 10);
        $hooks->add_hook('pre_item_delete', [$this, 'handle_product_attributes_delete'], 10);
    }

    /**
     * Hook: Add Product Attributes tab to item display
     *
     * @param array $tabs Current tabs array
     * @param string $stock_id The item stock ID
     * @return array Modified tabs array
     */
    function add_product_attributes_tab($tabs, $stock_id) {
        // Add Product Attributes tab
        $tabs['product_attributes'] = [
            'title' => _('Product Attributes'),
            'content' => $this->render_product_attributes_tab($stock_id)
        ];

        return $tabs;
    }

    /**
     * Hook: Handle product attributes save
     *
     * @param array $item_data The item data being saved
     * @param string $stock_id The item stock ID
     * @return array Modified item data
     */
    function handle_product_attributes_save($item_data, $stock_id) {
        // Handle saving product attributes data
        // This will be called before the item is saved
        $this->save_product_attributes($stock_id, $_POST);

        return $item_data;
    }

    /**
     * Hook: Handle product attributes delete
     *
     * @param string $stock_id The item stock ID being deleted
     */
    function handle_product_attributes_delete($stock_id) {
        // Handle cleanup when item is deleted
        $this->delete_product_attributes($stock_id);
    }

    /**
     * Render the Product Attributes tab content
     *
     * @param string $stock_id The item stock ID
     * @return string HTML content for the tab
     */
    private function render_product_attributes_tab($stock_id) {
        global $path_to_root;

        // Include the composer autoloader
        $autoloader = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        try {
            // Create DAO and get product attributes for this item
            $db = new \Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter();
            $dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);

            // Get assigned categories for this product
            $assignedCategories = $dao->getAssignedCategoriesForProduct($stock_id);

            // Render the tab content
            ob_start();
            ?>
            <div class="tab-content">
                <h3><?php echo _('Product Attributes'); ?></h3>

                <?php if (empty($assignedCategories)): ?>
                    <p><?php echo _('No product attributes assigned to this item.'); ?></p>
                    <p><?php echo _('Attributes are assigned at the category level in the Product Attributes admin.'); ?></p>
                <?php else: ?>
                    <table class="tablestyle">
                        <thead>
                            <tr>
                                <th><?php echo _('Category'); ?></th>
                                <th><?php echo _('Values'); ?></th>
                                <th><?php echo _('Variations'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedCategories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>
                                        <?php
                                        $values = $dao->getValuesForCategory($category['id']);
                                        echo implode(', ', array_column($values, 'value'));
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $variationCount = $dao->getVariationCountForProductCategory($stock_id, $category['id']);
                                        echo $variationCount;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();

        } catch (Exception $e) {
            return '<div class="error">' . _('Error loading product attributes: ') . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Save product attributes data
     *
     * @param string $stock_id The item stock ID
     * @param array $post_data The POST data
     */
    private function save_product_attributes($stock_id, $post_data) {
        // Implementation for saving product attributes
        // This would handle any product-specific attribute data
        // For now, product attributes are managed at category level
    }

    /**
     * Delete product attributes data
     *
     * @param string $stock_id The item stock ID
     */
    private function delete_product_attributes($stock_id) {
        global $path_to_root;

        // Include the composer autoloader
        $autoloader = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        try {
            // Create DAO and clean up product attributes
            $db = new \Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter();
            $dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);

            // Delete any product-specific attribute data
            // Note: Category assignments are managed separately

        } catch (Exception $e) {
            error_log('FA_ProductAttributes: Error deleting product attributes: ' . $e->getMessage());
        }
    }

    /**
     * Create database schema for the module
     *
     * @param string $modulePath
     * @throws Exception
     */
    private function createDatabaseSchema($modulePath)
    {
        // Include the composer autoloader if available
        $autoloader = $modulePath . '/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        // Create database adapter and schema manager
        $db = new \Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter();
        $schemaManager = new \Ksfraser\FA_ProductAttributes\Schema\SchemaManager();
        
        // Create the schema
        $schemaManager->ensureSchema($db);
    }
}
