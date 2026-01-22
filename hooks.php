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

            // Get individual value assignments for this product
            $individualAssignments = $dao->listAssignments($stock_id);

            // Group individual assignments by category
            $assignmentsByCategory = [];
            foreach ($individualAssignments as $assignment) {
                $catId = $assignment['category_id'];
                if (!isset($assignmentsByCategory[$catId])) {
                    $assignmentsByCategory[$catId] = [
                        'category' => $assignment['category_label'],
                        'values' => []
                    ];
                }
                $assignmentsByCategory[$catId]['values'][] = $assignment['value_label'];
            }

            // Render the tab content
            ob_start();
            ?>
            <div class="tab-content">
                <h3><?php echo _('Product Attributes'); ?></h3>

                <?php if (empty($assignedCategories) && empty($individualAssignments)): ?>
                    <p><?php echo _('No product attributes assigned to this item.'); ?></p>
                    <p><?php echo _('Attributes can be assigned at the category level in the Product Attributes admin, or individual values can be assigned here.'); ?></p>
                <?php else: ?>
                    <table class="tablestyle">
                        <thead>
                            <tr>
                                <th><?php echo _('Category'); ?></th>
                                <th><?php echo _('Assigned Values'); ?></th>
                                <th><?php echo _('Assignment Type'); ?></th>
                                <th><?php echo _('Variations'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Show individual assignments first
                            foreach ($assignmentsByCategory as $catId => $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['category']); ?></td>
                                    <td><?php echo htmlspecialchars(implode(', ', $data['values'])); ?></td>
                                    <td><?php echo _('Individual Values'); ?></td>
                                    <td><?php echo count($data['values']); ?></td>
                                </tr>
                            <?php endforeach;

                            // Show category assignments (excluding those with individual assignments)
                            foreach ($assignedCategories as $category):
                                if (!isset($assignmentsByCategory[$category['id']])):
                                    $values = $dao->getValuesForCategory($category['id']);
                                    $valueLabels = array_column($values, 'value');
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['label']); ?></td>
                                    <td><?php echo htmlspecialchars(implode(', ', $valueLabels)); ?></td>
                                    <td><?php echo _('All Category Values'); ?></td>
                                    <td><?php echo count($values); ?></td>
                                </tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h4><?php echo _('Manage Individual Value Assignments'); ?></h4>
                <p><?php echo _('Select specific attribute values for this product. Individual assignments override category assignments.'); ?></p>

                <form method="post" action="">
                    <?php
                    // Get all available categories
                    $allCategories = $dao->listCategories();

                    foreach ($allCategories as $category):
                        $categoryValues = $dao->getValuesForCategory($category['id']);
                        if (empty($categoryValues)) continue;

                        // Get currently assigned values for this category
                        $assignedValueIds = array_column(
                            array_filter($individualAssignments, function($a) use ($category) {
                                return $a['category_id'] == $category['id'];
                            }),
                            'value_id'
                        );
                    ?>
                    <fieldset>
                        <legend><?php echo htmlspecialchars($category['label']); ?> (<?php echo htmlspecialchars($category['code']); ?>)</legend>

                        <?php foreach ($categoryValues as $value): ?>
                            <label style="display: inline-block; margin-right: 15px;">
                                <input type="checkbox"
                                       name="attribute_values[<?php echo $category['id']; ?>][]"
                                       value="<?php echo $value['id']; ?>"
                                       <?php echo in_array($value['id'], $assignedValueIds) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($value['value']); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <?php endforeach; ?>

                    <div style="margin-top: 15px;">
                        <button type="submit" name="save_product_attributes" value="1" class="btn btn-primary">
                            <?php echo _('Save Attribute Assignments'); ?>
                        </button>
                    </div>
                </form>
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
        global $path_to_root;

        // Include the composer autoloader
        $autoloader = $path_to_root . '/modules/FA_ProductAttributes/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        try {
            // Create DAO
            $db = new \Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter();
            $dao = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($db);

            // Handle individual value assignments
            if (isset($post_data['attribute_values']) && is_array($post_data['attribute_values'])) {
                // First, remove all existing individual assignments for this product
                $existingAssignments = $dao->listAssignments($stock_id);
                foreach ($existingAssignments as $assignment) {
                    $dao->deleteAssignment($assignment['id']);
                }

                // Add new assignments based on form data
                foreach ($post_data['attribute_values'] as $categoryId => $valueIds) {
                    if (is_array($valueIds)) {
                        foreach ($valueIds as $valueId) {
                            $dao->addAssignment($stock_id, (int)$categoryId, (int)$valueId);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            error_log('FA_ProductAttributes: Error saving product attributes: ' . $e->getMessage());
            // In a production environment, you might want to show an error message to the user
        }
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

            // Delete individual value assignments
            $existingAssignments = $dao->listAssignments($stock_id);
            foreach ($existingAssignments as $assignment) {
                $dao->deleteAssignment($assignment['id']);
            }

            // Note: Category assignments are managed separately and should be cleaned up by the admin interface

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
