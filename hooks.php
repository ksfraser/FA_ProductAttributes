<?php

// FrontAccounting hooks file for the module.
// When installed under FA as modules/FA_ProductAttributes, this adds the admin page.

define('SS_FA_ProductAttributes', 112 << 8);

class hooks_FA_ProductAttributes extends hooks
{
    var $module_name = 'Product Attributes';

    function install()
    {
        // Standard FA module SQL installation array
        $sql = array(
            "CREATE TABLE IF NOT EXISTS 0_product_attribute_categories (
                id INT(11) NOT NULL AUTO_INCREMENT,
                code VARCHAR(64) NOT NULL,
                label VARCHAR(64) NOT NULL,
                description VARCHAR(255) NULL,
                sort_order INT(11) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_code (code)
            )",

            "CREATE TABLE IF NOT EXISTS 0_product_attribute_values (
                id INT(11) NOT NULL AUTO_INCREMENT,
                category_id INT(11) NOT NULL,
                value VARCHAR(64) NOT NULL,
                slug VARCHAR(32) NOT NULL,
                sort_order INT(11) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 1,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_category_slug (category_id, slug),
                KEY idx_category (category_id)
            )",

            "CREATE TABLE IF NOT EXISTS 0_product_attribute_assignments (
                id INT(11) NOT NULL AUTO_INCREMENT,
                stock_id VARCHAR(32) NOT NULL,
                category_id INT(11) NOT NULL,
                value_id INT(11) NOT NULL,
                sort_order INT(11) NOT NULL DEFAULT 0,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_stock_category_value (stock_id, category_id, value_id),
                KEY idx_stock (stock_id),
                KEY idx_category (category_id),
                KEY idx_value (value_id)
            )"
        );

        // Install composer dependencies (additional setup)
        global $path_to_root;
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

        return $sql; // Return the SQL array for FA's module system
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
