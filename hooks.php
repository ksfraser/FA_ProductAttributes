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

        // Check if fa-hooks dependency is installed
        $faHooksPath = $path_to_root . '/modules/0fa-hooks';
        if (!file_exists($faHooksPath . '/hooks.php')) {
            // Fallback to original name
            $faHooksPath = $path_to_root . '/modules/fa-hooks';
            if (!file_exists($faHooksPath . '/hooks.php')) {
                // Try alternative naming if renamed for loading order
                $altPaths = [
                    $path_to_root . '/modules/00-fa-hooks/hooks.php',
                    $path_to_root . '/modules/aa-fa-hooks/hooks.php'
                ];
                $found = false;
                foreach ($altPaths as $altPath) {
                    if (file_exists($altPath)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    display_error('FA-Hooks module must be installed before Product Attributes. Please install 0fa-hooks module first.');
                    return false;
                }
            }
        }

        // Install composer dependencies using dedicated installer class
        $module_path = $path_to_root . '/modules/FA_ProductAttributes';
        $installer = new \Ksfraser\FA_ProductAttributes\Install\ComposerInstaller($module_path);
        $result = $installer->install();

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

    /**
     * Create database schema programmatically
     *
     * @param string $module_path The path to the module
     */
    private function createDatabaseSchema($module_path)
    {
        $schema_file = $module_path . '/sql/schema.sql';
        if (!file_exists($schema_file)) {
            throw new Exception('Schema file not found: ' . $schema_file);
        }

        $sql = file_get_contents($schema_file);
        if ($sql === false) {
            throw new Exception('Failed to read schema file: ' . $schema_file);
        }

        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        global $db;
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $result = db_query($statement, 'Failed to execute schema statement');
                if (!$result) {
                    throw new Exception('Failed to execute schema statement: ' . $statement);
                }
            }
        }
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
        $hooks->add_hook('item_display_tab_headers', [__CLASS__, 'static_add_tab_headers'], 10);
        $hooks->add_hook('item_display_tab_content', [__CLASS__, 'static_get_tab_content'], 10);
        $hooks->add_hook('pre_item_write', [__CLASS__, 'static_handle_product_attributes_save'], 10);
        $hooks->add_hook('pre_item_delete', [__CLASS__, 'static_handle_product_attributes_delete'], 10);

        // Register extension points for plugins
        $hooks->registerHookPoint('attributes_tab_content', 'product_attributes');
        $hooks->registerHookPoint('attributes_save', 'product_attributes');
        $hooks->registerHookPoint('attributes_delete', 'product_attributes');
    }

    /**
     * Static hook callback for adding tab headers
     *
     * @param \Ksfraser\FA_Hooks\TabCollection $collection Current tab collection
     * @param string $stock_id The item stock ID
     * @return \Ksfraser\FA_Hooks\TabCollection Modified tab collection
     */
    public static function static_add_tab_headers($collection, $stock_id) {
        $service = self::static_get_product_attributes_service();
        $integration = new \Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration($service);
        return $integration->addTabHeaders($collection, $stock_id);
    }

    /**
     * Static hook callback for getting tab content
     *
     * @param string $content Current content
     * @param string $stock_id The item stock ID
     * @param string $selected_tab The selected tab
     * @return string Tab content
     */
    public static function static_get_tab_content($content, $stock_id, $selected_tab) {
        $service = self::static_get_product_attributes_service();
        $integration = new \Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration($service);
        return $integration->getTabContent($content, $stock_id, $selected_tab);
    }

    /**
     * Static hook callback for handling product attributes save
     *
     * @param array $item_data The item data being saved
     * @param string $stock_id The item stock ID
     * @return array Modified item data
     */
    public static function static_handle_product_attributes_save($item_data, $stock_id) {
        $service = self::static_get_product_attributes_service();
        $handler = new \Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler($service);
        return $handler->handle_product_attributes_save($item_data, $stock_id);
    }

    /**
     * Static hook callback for handling product attributes delete
     *
     * @param string $stock_id The item stock ID being deleted
     */
    public static function static_handle_product_attributes_delete($stock_id) {
        $service = self::static_get_product_attributes_service();
        $handler = new \Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler($service);
        $handler->handle_product_attributes_delete($stock_id);
    }

    /**
     * Static method to get ProductAttributesService instance
     *
     * @return \Ksfraser\FA_ProductAttributes\Service\ProductAttributesService
     */
    private static function static_get_product_attributes_service() {
        global $path_to_root;

        // Include the composer autoloader
        $autoloader = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        // Create service instance
        $db = new \Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter();
        $dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);

        return new \Ksfraser\FA_ProductAttributes\Service\ProductAttributesService($dao, $db);
    }

    /**
     * Get the ProductAttributesService instance
     *
     * @return \Ksfraser\FA_ProductAttributes\Service\ProductAttributesService
     */
    private function get_product_attributes_service() {
        global $path_to_root;

        // Include the composer autoloader
        $autoloader = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        // Create service instance
        $db = new \Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter();
        $dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);

        return new \Ksfraser\FA_ProductAttributes\Service\ProductAttributesService($dao, $db);
    }
}
