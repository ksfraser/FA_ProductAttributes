<?php

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Dao\MediaAttachmentsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Handler\ProductAttributesHandler;
use Ksfraser\FA_ProductAttributes\Plugin\PluginLoader;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use Ksfraser\FA_ProductAttributes\Tabs\AttributesTab;
use Ksfraser\FA_ProductAttributes\Tabs\ShippingTab;
use Ksfraser\FA_ProductAttributes\Tabs\IdentifiersTab;
use Ksfraser\FA_ProductAttributes\Tabs\LifecycleTab;
use Ksfraser\FA_ProductAttributes\Tabs\MediaTab;
use Ksfraser\FA_ProductAttributes\Tabs\UrlsTab;
use Ksfraser\FA_ProductAttributes\Tabs\WarrantyTab;
use Ksfraser\FA_ProductAttributes\Tabs\VariationsTab;
use FrontAccounting\ProductAttributes\Variations\Dao\VariationsDao;
use FrontAccounting\ProductAttributes\Variations\Service\FrontAccountingVariationService;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use FrontAccounting\ProductAttributes\Plugin\TabRegistry;

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
                    'SA_OPEN'
                );
                $app->add_rapp_function(
                    2,
                    _('Lifecycle Flags'),
                    $path_to_root . '/modules/' . $this->module_name . '/public/lifecycle-flags.php',
                    'SA_OPEN'
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
        $GLOBALS['fa_product_attributes_tab_registry'] = null;
        return true;
    }

    public function register_hooks()
    {
        // FA hook_invoke_all() calls hook methods directly on this class.
    }

    public function item_display_tab_headers($tabs, $stockId = '')
    {
        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return $tabs;
        }

        $resolvedStockId = $stockId;
        if ($resolvedStockId === '' && isset($_POST['stock_id'])) {
            $resolvedStockId = (string)$_POST['stock_id'];
        }

        if (is_object($tabs) && method_exists($tabs, 'createTab')) {
            foreach ($this->get_tab_registry()->getAvailableTabs((string)$resolvedStockId) as $tab) {
                $tabs->createTab($tab->getTabKey(), $tab->getTabLabel());
            }
            return $tabs;
        }

        if (!is_array($tabs)) {
            return $tabs;
        }

        foreach ($this->get_tab_registry()->getAvailableTabs((string)$resolvedStockId) as $tab) {
            $tabs[$tab->getTabKey()] = array(
                $tab->getTabLabel(),
                $resolvedStockId
            );
        }

        return $tabs;
    }

    public function item_display_tab_content($stockId = '', $selectedTab = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return false;
        }

        $resolvedStockId = $stockId;
        if ($resolvedStockId === '' && isset($_POST['stock_id'])) {
            $resolvedStockId = (string)$_POST['stock_id'];
        }

        $tab = $this->get_tab_registry()->getTab((string)$selectedTab);
        if ($tab !== null) {
            $tab->renderTabContent((string)$resolvedStockId);
            return true;
        }

        return false;
    }

    public function post_item_write($itemData, $stockId = '')
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

        foreach ($this->get_tab_registry()->getAll() as $tab) {
            if ($tab instanceof \FrontAccounting\ProductAttributes\Plugin\ProductAttributeTabInterface) {
                $tab->handleSave((string)$stockId, $_POST);
            }
        }

        return $itemData;
    }

    public function pre_item_delete($stockId = '')
    {
        $this->load_plugins_on_demand();

        if ($this->can_check_access() && !$this->has_product_attributes_access()) {
            return null;
        }

        foreach ($this->get_tab_registry()->getAll() as $tab) {
            if ($tab instanceof \FrontAccounting\ProductAttributes\Plugin\ProductAttributeTabInterface) {
                $tab->handleDelete((string)$stockId);
            }
        }

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

    private function get_tab_registry(): TabRegistry
    {
        if (isset($GLOBALS['fa_product_attributes_tab_registry'])
            && $GLOBALS['fa_product_attributes_tab_registry'] instanceof TabRegistry) {
            return $GLOBALS['fa_product_attributes_tab_registry'];
        }

        $services = $this->get_services();
        $registry = new TabRegistry();

        $registry->register(new AttributesTab($services['service'], $services['handler']));
        $registry->register(new ShippingTab($services['shipping_dao']));
        $registry->register(new IdentifiersTab($services['identifiers_dao']));
        $registry->register(new LifecycleTab($services['lifecycle_dao'], $services['lifecycle_flag_defs_dao']));
        $registry->register(new MediaTab($services['media_dao']));
        $registry->register(new UrlsTab($services['media_attachments_dao']));
        $registry->register(new WarrantyTab($services['warranty_dao']));
        $registry->register(new VariationsTab($services['variations_dao']));

        $GLOBALS['fa_product_attributes_tab_registry'] = $registry;
        return $registry;
    }

    private function can_check_access()
    {
        return function_exists('user_check_access');
    }

    private function has_product_attributes_access()
    {
        global $security_areas;

        if (!isset($security_areas['SA_PRODUCT_ATTRIBUTES'])
            && !isset($security_areas['SA_FA_ProductAttributes'])) {
            return true;
        }

        $hasAccess = false;
        if (isset($security_areas['SA_PRODUCT_ATTRIBUTES'])) {
            $hasAccess = $hasAccess || user_check_access('SA_PRODUCT_ATTRIBUTES');
        }
        if (isset($security_areas['SA_FA_ProductAttributes'])) {
            $hasAccess = $hasAccess || user_check_access('SA_FA_ProductAttributes');
        }

        return $hasAccess;
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
        $shippingDao = new ShippingAttributesDao($db);
        $identifiersDao = new ProductIdentifiersDao($db);
        $lifecycleDao = new ProductLifecycleDao($db);
        $mediaDao = new ProductMediaDao($db);
        $warrantyDao = new ProductWarrantyDao($db);
        $lifecycleFlagDefsDao = new LifecycleFlagDefsDao($db);
        $mediaAttachmentsDao = new MediaAttachmentsDao($db);
        $variationsDao = new VariationsDao($db, new \FrontAccounting\ProductAttributes\Dao\ProductAttributesDao($db));
        $service = new ProductAttributesService($dao, $db);
        $handler = new ProductAttributesHandler($service);

        $GLOBALS['fa_product_attributes_services_cache'] = array(
            'service' => $service,
            'handler' => $handler,
            'shipping_dao' => $shippingDao,
            'identifiers_dao' => $identifiersDao,
            'lifecycle_dao' => $lifecycleDao,
            'media_dao' => $mediaDao,
            'media_attachments_dao' => $mediaAttachmentsDao,
            'warranty_dao' => $warrantyDao,
            'lifecycle_flag_defs_dao' => $lifecycleFlagDefsDao,
            'variations_dao' => $variationsDao,
        );

        return $GLOBALS['fa_product_attributes_services_cache'];
    }
}
