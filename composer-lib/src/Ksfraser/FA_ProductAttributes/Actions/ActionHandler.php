<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

class ActionHandler
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(string $action, array $postData): ?string
    {
        try {
            switch ($action) {
                case 'upsert_category':
                    $handler = new UpsertCategoryAction($this->dao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'delete_category':
                    $handler = new DeleteCategoryAction($this->dao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'upsert_value':
                    $handler = new UpsertValueAction($this->dao);
                    return $handler->handle($postData);

                case 'delete_value':
                    $handler = new DeleteValueAction($this->dao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'add_assignment':
                    $handler = new AddAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'delete_assignment':
                    $handler = new DeleteAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'add_category_assignment':
                    $handler = new AddCategoryAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'remove_category_assignment':
                    $handler = new RemoveCategoryAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'generate_variations':
                    // Delegate to variations plugin
                    return $this->handlePluginAction('generate_variations', $postData);

                case 'update_category_assignments':
                    $handler = new UpdateCategoryAssignmentsAction($this->dao);
                    return $handler->handle($postData);

                case 'create_child':
                    // Delegate to variations plugin
                    return $this->handlePluginAction('create_child', $postData);

                case 'update_product_types':
                    // Delegate to variations plugin
                    return $this->handlePluginAction('update_product_types', $postData);
                    return $handler->handle($postData);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            display_error("Error handling action '$action': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle actions that are delegated to plugins
     */
    private function handlePluginAction(string $action, array $postData): ?string
    {
        // Use hooks to allow plugins to handle this action
        if (function_exists('fa_hooks')) {
            $hooks = fa_hooks();
            $result = $hooks->call_hook('fa_product_attributes_plugin_action', $action, $postData);
            if ($result !== null) {
                return $result;
            }
        }

        // Fallback: try to load the plugin directly
        return $this->loadPluginActionHandler($action, $postData);
    }

    /**
     * Load plugin action handler directly (fallback)
     */
    private function loadPluginActionHandler(string $action, array $postData): ?string
    {
        global $path_to_root;

        // Try to load variations plugin action handler
        $pluginPath = $path_to_root . '/modules/FA_ProductAttributes_Variations';
        if (file_exists($pluginPath . '/hooks.php')) {
            // Include plugin autoloader
            $autoloader = $pluginPath . '/composer-lib/vendor/autoload.php';
            if (file_exists($autoloader)) {
                require_once $autoloader;
            }

            // Try to instantiate the plugin's action handler
            $pluginClass = 'hooks_FA_ProductAttributes_Variations';
            if (class_exists($pluginClass) && method_exists($pluginClass, 'handlePluginAction')) {
                $pluginInstance = new $pluginClass();
                return $pluginInstance->handlePluginAction($action, $postData);
            }
        }

        return null;
    }
}