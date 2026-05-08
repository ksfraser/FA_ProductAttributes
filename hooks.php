<?php

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler;
use Ksfraser\FA_ProductAttributes\Integration\ItemsIntegration;
use Ksfraser\FA_ProductAttributes\Plugin\PluginLoader;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

if (!class_exists('hooks')) {
    class hooks
    {
    }
}

if (!defined('FA_PRODUCT_ATTRIBUTES_HOOKS_LOADED')) {
    define('FA_PRODUCT_ATTRIBUTES_HOOKS_LOADED', true);
}

if (!defined('SS_PRODUCT_ATTRIBUTES')) {
    define('SS_PRODUCT_ATTRIBUTES', 115 << 8);
}

if (!defined('SS_FA_ProductAttributes')) {
    define('SS_FA_ProductAttributes', SS_PRODUCT_ATTRIBUTES);
}

if (!defined('SA_PRODUCT_ATTRIBUTES')) {
    define('SA_PRODUCT_ATTRIBUTES', SS_PRODUCT_ATTRIBUTES | 1);
}

if (!defined('SA_FA_ProductAttributes')) {
    define('SA_FA_ProductAttributes', SA_PRODUCT_ATTRIBUTES);
}

class hooks_FA_ProductAttributes extends hooks
{
    /** @var string */
    var $module_name = 'FA_ProductAttributes';

    public function __construct()
    {
        $this->load_autoloader();
    }

    public function install()
    {
        return true;
    }

    public function install_options($app)
    {
        global $path_to_root;

        switch ($app->id) {
            case 'stock':
                $app->add_rapp_function(
                    2,
                    _('Product Attributes'),
                    $path_to_root . '/modules/' . $this->module_name . '/public/index.php',
                    'SA_PRODUCT_ATTRIBUTES'
                );
                break;
        }
    }

    public function install_access()
    {
        $security_areas = array(
            'SA_PRODUCT_ATTRIBUTES' => array(SS_PRODUCT_ATTRIBUTES | 1, _('Product Attributes')),
            'SA_FA_ProductAttributes' => array(SS_PRODUCT_ATTRIBUTES | 1, _('Product Attributes')),
        );
        $security_sections = array(
            SS_PRODUCT_ATTRIBUTES => _('Product Attributes'),
        );

        return array($security_areas, $security_sections);
    }

    public function activate()
    {
        $this->register_hooks();
        return true;
    }

    public function activate_extension($company, $check_only = true)
    {
        if (!$check_only) {
            $this->register_hooks();
        }

        return true;
    }

    public function deactivate()
    {
        unset($GLOBALS['fa_product_attributes_services_cache']);
        return true;
    }

    public function register_hooks()
    {
        // FA hook_invoke_all() calls hook methods directly on this class.
        // Keep this lifecycle method for parity with the historical implementation.
    }

    public function item_display_tab_headers($tabs, $stockId = '')
    {
        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return $tabs;
        }

        if (is_object($tabs) && method_exists($tabs, 'createTab')) {
            return $this->get_items_integration()->addTabHeaders($tabs, (string)$stockId);
        }

        if (!is_array($tabs)) {
            return $tabs;
        }

        $resolvedStockId = $stockId;
        if ($resolvedStockId === '' && isset($_POST['stock_id'])) {
            $resolvedStockId = (string)$_POST['stock_id'];
        }

        $tabs['product_attributes'] = array(
            _('Product Attributes'),
            $resolvedStockId
        );

        return $tabs;
    }

    public function item_display_tab_content($stockId = '', $selectedTab = '')
    {
        $this->load_plugins_on_demand();

        if (!preg_match('/^product_attributes/', (string)$selectedTab)) {
            return false;
        }
        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return false;
        }

        $this->get_items_integration()->getTabContent((string)$stockId, (string)$selectedTab);

        return true;
    }

    public function pre_item_write($itemData, $stockId = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return is_array($itemData) ? $itemData : array();
        }

        if (!is_array($itemData)) {
            $itemData = array();
        }

        if ($stockId === '' && isset($itemData['stock_id'])) {
            $stockId = (string)$itemData['stock_id'];
        }

        return $this->get_product_attributes_handler()
            ->handle_product_attributes_save($itemData, (string)$stockId);
    }

    public function pre_item_delete($stockId = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return null;
        }

        $this->get_product_attributes_handler()
            ->handle_product_attributes_delete((string)$stockId);

        return null;
    }

    private function load_autoloader()
    {
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoloadPath)) {
            $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        }
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    private function load_plugins_on_demand()
    {
        if (class_exists(PluginLoader::class)) {
            PluginLoader::getInstance()->loadPluginsOnDemand();
        }
    }

    private function can_check_access()
    {
        return function_exists('user_check_access');
    }

    private function has_product_attributes_access()
    {
        return user_check_access('SA_PRODUCT_ATTRIBUTES')
            || user_check_access('SA_FA_ProductAttributes');
    }

    private function get_items_integration()
    {
        $services = $this->get_services();
        return $services['integration'];
    }

    private function get_product_attributes_handler()
    {
        $services = $this->get_services();
        return $services['handler'];
    }

    private function get_services()
    {
        if (isset($GLOBALS['fa_product_attributes_services_cache'])
            && is_array($GLOBALS['fa_product_attributes_services_cache'])) {
            return $GLOBALS['fa_product_attributes_services_cache'];
        }

        $tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
        $db = new FrontAccountingDbAdapter($tablePrefix);
        $dao = new ProductAttributesDao($db);
        $service = new ProductAttributesService($dao, $db);

        $GLOBALS['fa_product_attributes_services_cache'] = array(
            'integration' => new ItemsIntegration($service),
            'handler' => new ProductAttributesHandler($service),
        );

        return $GLOBALS['fa_product_attributes_services_cache'];
    }
}
