<?php

// FrontAccounting hooks file for the module.
// When installed under FA as modules/FA_ProductAttributes, this adds the admin page.

define('SS_FA_ProductAttributes', 112 << 8);

class hooks_FA_ProductAttributes extends hooks
{
    var $module_name = 'FA_ProductAttributes';

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
                    'SA_FA_ProductAttributes'
                );
                break;
        }
    }

    function install_access()
    {
        $security_sections[SS_FA_ProductAttributes] = _("Product Attributes");
        $security_areas['SA_FA_ProductAttributes'] = array(SS_FA_ProductAttributes | 101, _("Product Attributes"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        global $db_connections, $path_to_root;

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
    /**
     * Register hooks for the module
     * Called during module initialization
     */
    function register_hooks() {
        global $path_to_root;

        // Register security extensions for this module
        if (function_exists('add_security_extensions')) {
            add_security_extensions();
        }

        // FA automatically calls hook methods on this class:
        // - item_display_tab_headers()
        // - item_display_tab_content()
        // - pre_item_write()
        // - pre_item_delete()
        // No manual registration needed - FA's hook_invoke_all() calls these methods

        // Note: fa_hooks.php is loaded on-demand by components that need it
        // to avoid loading autoloaders during FA's early initialization
    }

    /**
     * Load plugins on demand when core functionality is accessed
     */
    private static function load_plugins_on_demand() {
        // Ensure autoloader is loaded before using PluginLoader
        self::ensure_autoloader_loaded();

        $pluginLoader = \Ksfraser\FA_ProductAttributes\Plugin\PluginLoader::getInstance();
        $pluginLoader->loadPluginsOnDemand();
    }

    /**
     * Ensure the composer autoloader is loaded
     */
    private static function ensure_autoloader_loaded() {
        global $path_to_root;

        // Load composer autoloader (needed for core classes in all environments)
        $autoloader = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        // Only load FA function mocks in testing/development environments
        // In production, FA provides the real functions
        if (defined('FA_TESTING') || getenv('FA_TESTING') || isset($_SERVER['FA_TESTING'])) {
            $famock = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/ksfraser/famock/php/FAMock.php';
            if (file_exists($famock)) {
                require_once $famock;
            }
        }
    }

    /**
     * FA hook: item_display_tab_headers
     * Called by FA to add tab headers to the items page
     */
    function item_display_tab_headers($tabs) {
        // Ensure security extensions are registered for this module
        if (function_exists('add_security_extensions')) {
            add_security_extensions();
        }

        // Add Product Attributes tab to the tabs array
        // FA expects tabs as arrays: array(title, stock_id_or_null)
        // Use null to disable tab if user lacks access
        $stock_id = $_POST['stock_id'] ?? '';
        $tabs['product_attributes'] = array(
            _('Product Attributes'),
            $stock_id  // Always show tab, handle permissions in content
        );

        return $tabs;
    }

    /**
     * Get a ProductAttributesDao instance
     * @return \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao
     */
    private function get_product_attributes_dao() {
        static $dao = null;
        if ($dao === null) {
            // Ensure autoloader is loaded
            self::ensure_autoloader_loaded();

            // Create database adapter
            $db_adapter = \Ksfraser\FA_ProductAttributes\Db\DatabaseAdapterFactory::create('fa');

            // Create DAO
            $dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db_adapter);
        }
        return $dao;
    }

    /**
     * FA hook: item_display_tab_content
     * Called by FA to display tab content in the items page
     * @param string $stock_id The item stock ID
     * @param string $selected_tab The currently selected tab
     * @return bool True if this hook handled the tab, false otherwise
     */
    function item_display_tab_content($stock_id, $selected_tab) {
        // Ensure security extensions are registered for this module
        if (function_exists('add_security_extensions')) {
            add_security_extensions();
        }
        if (function_exists('add_access_extensions')) {
            add_access_extensions();
        }

        // Only handle tabs that start with 'product_attributes'
        if (!preg_match('/^product_attributes/', $selected_tab)) {
            return false; // Not our tab, let others handle it
        }

        // Check access
        if (!user_check_access('SA_FA_ProductAttributes')) {
            return false; // No access, don't handle
        }

        // Handle the tab content
        try {
            global $path_to_root;

            // Temporarily use simple content without FA UI functions to test
            echo "<div style='padding: 20px; border: 1px solid #ccc; margin: 10px;'>";
            echo "<h3>Product Attributes Tab</h3>";
            echo "<p>Selected tab: {$selected_tab}</p>";
            echo "<p>Stock ID: {$stock_id}</p>";

            // Try to get assignments without using FA UI functions
            $dao = $this->get_product_attributes_dao();
            $assignments = $dao->listAssignments($stock_id);

            echo "<h4>Current Assignments:</h4>";
            if (empty($assignments)) {
                echo "<p>No attributes assigned to this item.</p>";
            } else {
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Category</th><th>Value</th><th>Actions</th></tr>";
                foreach ($assignments as $assignment) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($assignment['category_label'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($assignment['value_label'] ?? '') . "</td>";
                    echo "<td><a href='#'>Edit</a> | <a href='#'>Remove</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
            }

            echo "<br><button onclick=\"window.open('" . $path_to_root . "/modules/FA_ProductAttributes/product_attributes_admin.php', '_blank');\">Manage Product Attributes</button>";
            echo "</div>";
            return true; // Successfully handled
        } catch (Exception $e) {
            display_error("Error displaying tab content: " . $e->getMessage());
            return true; // We handled it (even though there was an error)
        }
    }

    /**
     * FA hook: pre_item_write
     * Called by FA before saving an item
     */
    function pre_item_write($item_data) {
        // Load plugins when core functionality is accessed
        self::load_plugins_on_demand();

        // Check user access before allowing attribute modifications
        if (!user_check_access('SA_FA_ProductAttributes')) {
            return $item_data; // Return unchanged data if no access
        }

        $service = $this->get_product_attributes_service();
        $handler = new \Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler($service);
        return $handler->handle_product_attributes_save($item_data, $item_data['stock_id'] ?? '');
    }

    /**
     * FA hook: pre_item_delete
     * Called by FA before deleting an item
     */
    function pre_item_delete($stock_id) {
        // Load plugins when core functionality is accessed
        self::load_plugins_on_demand();

        // Check user access before allowing attribute deletions
        if (!user_check_access('SA_FA_ProductAttributes')) {
            return null; // Allow deletion to proceed without touching attributes
        }

        $service = $this->get_product_attributes_service();
        $handler = new \Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler($service);
        $handler->handle_product_attributes_delete($stock_id);
        return null; // FA expects null return for delete hooks
    }
}

// ============================================================================
// Hook Registration and Plugin Loading (runs on every page load)
// ============================================================================

/**
 * Initialize hooks and load plugins on every page load
 */
function fa_product_attributes_init() {
    global $path_to_root;

    // Register core hooks
    $core_hooks = new hooks_FA_ProductAttributes();
    $core_hooks->register_hooks();

    // Plugin loading is now handled lazily when hooks are triggered
    // This ensures plugins are loaded only when needed
}

// Initialize on every page load
fa_product_attributes_init();
