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

if (!defined('SA_PRODUCT_ATTRIBUTES')) {
    define('SA_PRODUCT_ATTRIBUTES', SS_PRODUCT_ATTRIBUTES | 1);
}

class hooks_FA_ProductAttributes extends hooks
{
    /** @var string */
    var $module_name = 'FA_ProductAttributes';

    /** @var bool */
    private static $bootstrapped = false;

    public function __construct()
    {
        $this->load_autoloader();
        $this->bootstrap_hooks();
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
        );
        $security_sections = array(
            SS_PRODUCT_ATTRIBUTES => _('Product Attributes'),
        );

        return array($security_areas, $security_sections);
    }

    public function activate()
    {
        $this->bootstrap_hooks();
        return true;
    }

    public function activate_extension($company, $check_only = true)
    {
        if (!$check_only) {
            $this->bootstrap_hooks();
        }

        return true;
    }

    public function deactivate()
    {
        unset($GLOBALS['fa_product_attributes_services_cache']);
        self::$bootstrapped = false;
        return true;
    }

    public function item_display_tab_headers($tabCollection, $stockId = '')
    {
        if (!is_object($tabCollection) || !method_exists($tabCollection, 'createTab')) {
            return $tabCollection;
        }

        return $this->get_items_integration()->addTabHeaders($tabCollection, (string)$stockId);
    }

    public function item_display_tab_content($current, $stockId = '', $tab = '')
    {
        $handled = $this->get_items_integration()->getTabContent((string)$stockId, (string)$tab);
        if ($handled && is_bool($current)) {
            return true;
        }

        return $current;
    }

    public function pre_item_write($itemData, $stockId = '')
    {
        if (!is_array($itemData)) {
            $itemData = array();
        }

        return $this->get_product_attributes_handler()
            ->handle_product_attributes_save($itemData, (string)$stockId);
    }

    public function pre_item_delete($stockId = '')
    {
        $this->get_product_attributes_handler()
            ->handle_product_attributes_delete((string)$stockId);
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

    private function bootstrap_hooks()
    {
        if (self::$bootstrapped) {
            return;
        }

        if (function_exists('fa_hooks')) {
            $hooksManager = fa_hooks();
            if (is_object($hooksManager)
                && method_exists($hooksManager, 'add_filter')
                && method_exists($hooksManager, 'add_action')) {
                $hooksManager->add_filter('item_display_tab_headers', array($this, 'item_display_tab_headers'), 10);
                $hooksManager->add_filter('item_display_tab_content', array($this, 'item_display_tab_content'), 10);
                $hooksManager->add_filter('pre_item_write', array($this, 'pre_item_write'), 10);
                $hooksManager->add_action('pre_item_delete', array($this, 'pre_item_delete'), 10);
            }
        }

        if (class_exists(PluginLoader::class)) {
            PluginLoader::getInstance()->loadPluginsOnDemand();
        }

        self::$bootstrapped = true;
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
