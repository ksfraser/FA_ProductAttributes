<?php

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Dao\MediaAttachmentsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductCustomAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductModifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductFulfillmentDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductCategoryHierarchyDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductShippingClassesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductCartRulesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductRelatedProductsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMeasurementUnitsDao;
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
use Ksfraser\FA_ProductAttributes\Tabs\TagsTab;
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
                $app->add_rapp_function(
                    2,
                    _('Brands / Manufacturers'),
                    $path_to_root . '/modules/' . $this->module_name . '/public/brands.php',
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

        $updates = array();

        $sqlDir = __DIR__ . '/sql';
        $files = array(
            '01_product_attribute_categories.sql',
            '02_product_attribute_values.sql',
            '03_product_attribute_assignments.sql',
            '04_product_attribute_category_assignments.sql',
            '05_product_hierarchy.sql',
            '06_product_shipping_attributes.sql',
            '07_product_identifiers.sql',
            '08_product_lifecycle.sql',
            '09_product_tags.sql',
            '10_product_tag_assignments.sql',
            '11_product_media.sql',
            '12_product_media_variation_links.sql',
            '13_product_media_attachments.sql',
            '14_product_warranty.sql',
            '15_product_lifecycle_flag_defs.sql',
            '16_product_lifecycle_flag_assignments.sql',
            '17_lifecycle_flag_defs_seed.sql',
            '18_product_identifier_lookups.sql',
            '19_product_attribute_categories_seed.sql',
            '20_product_custom_attributes.sql',
            '21_product_modifier_lists.sql',
            '22_product_modifiers.sql',
            '23_product_modifier_list_assignments.sql',
            '24_product_fulfillment.sql',
            '25_product_category_hierarchy.sql',
            '26_product_shipping_classes.sql',
            '27_product_cart_rules.sql',
            '28_product_related_products.sql',
            '29_product_measurement_units.sql',
            '30_product_attribute_extras.sql',
        );

        foreach ($files as $file) {
            if (file_exists($sqlDir . '/' . $file)) {
                if ($file === '30_product_attribute_extras.sql') {
                    // Probe on the first upgraded column: the ALTERs run once on
                    // existing installs; fresh installs already get the columns
                    // from base schema files 02/03/06 so this file is skipped.
                    $updates[$file] = array('product_attribute_values', 'color');
                } else {
                    $updates[$file] = array($this->module_name);
                }
            }
        }

        if (!empty($updates)) {
            return $this->update_databases($company, $updates, $check_only);
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
        // Shared utility: ensure Composer dependencies are installed (runs once).
        $composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
        if (file_exists($composerDepsPath)) {
            require_once $composerDepsPath;
            \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
        }

        // Try vendor autoloader.
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
        $registry->register(new IdentifiersTab($services['identifiers_dao'], $services['identifier_lookups_dao']));
        $registry->register(new LifecycleTab($services['lifecycle_dao'], $services['lifecycle_flag_defs_dao']));
        $registry->register(new MediaTab($services['media_dao']));
        $registry->register(new UrlsTab($services['media_attachments_dao']));
        $registry->register(new WarrantyTab($services['warranty_dao']));
        $registry->register(new VariationsTab($services['variations_dao']));
        $registry->register(new TagsTab($services['dao'], $services['tags_dao']));

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
            && is_array($GLOBALS['fa_product_attributes_services_cache'])
            && isset($GLOBALS['fa_product_attributes_services_cache']['tags_dao'])) {
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
        $tagsDao = new ProductTagsDao($db);
        $identifierLookupsDao = new IdentifierLookupsDao($db);
        $customAttributesDao = new ProductCustomAttributesDao($db);
        $modifiersDao = new ProductModifiersDao($db);
        $fulfillmentDao = new ProductFulfillmentDao($db);
        $categoryHierarchyDao = new ProductCategoryHierarchyDao($db);
        $shippingClassesDao = new ProductShippingClassesDao($db);
        $cartRulesDao = new ProductCartRulesDao($db);
        $relatedProductsDao = new ProductRelatedProductsDao($db);
        $measurementUnitsDao = new ProductMeasurementUnitsDao($db);
        $variationsDao = new VariationsDao($db, new \FrontAccounting\ProductAttributes\Dao\ProductAttributesDao($db));
        $service = new ProductAttributesService($dao, $db);
        $handler = new ProductAttributesHandler($service);

        $GLOBALS['fa_product_attributes_services_cache'] = array(
            'service' => $service,
            'handler' => $handler,
            'dao' => $dao,
            'shipping_dao' => $shippingDao,
            'identifiers_dao' => $identifiersDao,
            'lifecycle_dao' => $lifecycleDao,
            'media_dao' => $mediaDao,
            'media_attachments_dao' => $mediaAttachmentsDao,
            'warranty_dao' => $warrantyDao,
            'lifecycle_flag_defs_dao' => $lifecycleFlagDefsDao,
            'tags_dao' => $tagsDao,
            'identifier_lookups_dao' => $identifierLookupsDao,
            'variations_dao' => $variationsDao,
            'custom_attributes_dao' => $customAttributesDao,
            'modifiers_dao' => $modifiersDao,
            'fulfillment_dao' => $fulfillmentDao,
            'category_hierarchy_dao' => $categoryHierarchyDao,
            'shipping_classes_dao' => $shippingClassesDao,
            'cart_rules_dao' => $cartRulesDao,
            'related_products_dao' => $relatedProductsDao,
            'measurement_units_dao' => $measurementUnitsDao,
        );

        return $GLOBALS['fa_product_attributes_services_cache'];
    }
}
