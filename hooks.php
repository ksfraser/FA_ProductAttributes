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
     * Get registered sub-tabs from plugins
     * @return array Array of sub-tab configurations
     */
    private function get_registered_subtabs() {
        $subtabs = array();

        // Allow plugins to register sub-tabs via hooks
        global $path_to_root;
        $hooksFile = $path_to_root . '/modules/FA_ProductAttributes/fa_hooks.php';
        if (file_exists($hooksFile)) {
            require_once $hooksFile;

            if (function_exists('fa_hooks')) {
                $hooks = fa_hooks();

                // Apply filter for sub-tab registrations
                $plugin_subtabs = $hooks->apply_filters('product_attributes_subtabs', array());
                $subtabs = array_merge($subtabs, $plugin_subtabs);
            }
        }

        return $subtabs;
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
            echo "<h3>Product Attributes for: {$stock_id}</h3>";

            // Get assignments and categories
            $dao = $this->get_product_attributes_dao();
            $assignments = $dao->listAssignments($stock_id);
            $categories = $dao->listCategories();

            // Check if this item is a parent (has variations)
            $isParent = $dao->getProductParent($stock_id) === null && !empty($dao->getVariationCountForProductCategory($stock_id, 0)); // Simple check

            // Product Attributes Controls
            echo "<div style='margin-bottom: 20px; padding: 10px; background-color: #f0f0f0; border-radius: 5px;'>";
            echo "<h4>Product Configuration:</h4>";
            echo "<form method='post' action='' style='display: inline;'>";
            echo "<input type='hidden' name='stock_id' value='" . htmlspecialchars($stock_id) . "'>";
            echo "<label><input type='checkbox' name='is_parent' value='1' " . ($isParent ? 'checked' : '') . "> This is a parent product (can have variations)</label> ";
            echo "<input type='submit' name='update_product_config' value='Update'>";
            echo "</form>";
            echo "</div>";

            // Plugin Admin Links
            echo "<div style='margin-bottom: 20px; padding: 10px; background-color: #e8f4f8; border-radius: 5px;'>";
            echo "<h4>Plugin Administration:</h4>";
            echo "<p><a href='./modules/FA_ProductAttributes/product_attributes_admin.php' target='_blank'>Product Attributes Admin</a></p>";
            // Plugin links will be added here dynamically when plugins are loaded
            echo "<p><em>Plugin admin links will appear here when plugins are activated.</em></p>";
            echo "</div>";

            // Get registered sub-tabs from plugins
            $subtabs = $this->get_registered_subtabs();
            $current_subtab = $_GET['subtab'] ?? ($subtabs ? array_key_first($subtabs) : 'core');

            // Sub-tabs navigation (dynamically generated from plugins)
            if (!empty($subtabs)) {
                echo "<div style='margin-bottom: 20px;'>";
                foreach ($subtabs as $tab_key => $tab_info) {
                    $is_active = ($current_subtab == $tab_key);
                    echo "<a href='?tab=product_attributes&subtab={$tab_key}' style='padding: 8px 12px; margin-right: 5px; text-decoration: none; " . ($is_active ? 'background-color: #007cba; color: white;' : 'background-color: #f0f0f0;') . "'>{$tab_info['title']}</a>";
                }
                echo "</div>";
            }

            // Display content based on sub-tab
            if (isset($subtabs[$current_subtab])) {
                // Plugin-provided sub-tab content
                $tab_info = $subtabs[$current_subtab];
                if (isset($tab_info['callback']) && is_callable($tab_info['callback'])) {
                    call_user_func($tab_info['callback'], $stock_id, $dao);
                } else {
                    echo "<p>Sub-tab '{$current_subtab}' is not properly configured.</p>";
                }
            } else {
                // Fallback: show basic core information
                echo "<h4>Product Attributes Core:</h4>";
                echo "<p>Welcome to Product Attributes. Install plugins to add functionality.</p>";
                echo "<p>Available plugins will register their own sub-tabs above.</p>";
            }

            // JavaScript for dynamic value loading
            echo "<script>
            document.querySelector('select[name=category_id]').addEventListener('change', function() {
                var categoryId = this.value;
                var valueSelect = document.getElementById('value_select');
                valueSelect.innerHTML = '<option value=\"\">Loading...</option>';
                valueSelect.disabled = true;

                if (categoryId) {
                    fetch('./modules/FA_ProductAttributes/api.php?action=get_values&category_id=' + categoryId)
                        .then(response => response.json())
                        .then(data => {
                            valueSelect.innerHTML = '<option value=\"\">Select Value</option>';
                            data.forEach(function(value) {
                                valueSelect.innerHTML += '<option value=\"' + value.id + '\">' + value.value + '</option>';
                            });
                            valueSelect.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            valueSelect.innerHTML = '<option value=\"\">Error loading values</option>';
                        });
                } else {
                    valueSelect.innerHTML = '<option value=\"\">Select Value</option>';
                    valueSelect.disabled = true;
                }
            });

            // Handle sub-tab navigation to maintain state
            document.querySelectorAll('a[href*=\"subtab=\"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    // Could add loading indicator here
                });
            });
            </script>";

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
