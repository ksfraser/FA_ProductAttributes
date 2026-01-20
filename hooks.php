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

        return true; // Installation should continue even if composer fails
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
     * Install composer dependencies for the module
     *
     * @param string $modulePath
     * @return array
     */
    private function installComposerDependencies($modulePath)
    {
        // Include the composer autoloader if it exists
        $autoloader = $modulePath . '/composer-lib/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }

        // Try to load the ComposerInstaller class
        $installerClass = 'Ksfraser\\FA_ProductAttributes\\Install\\ComposerInstaller';

        if (class_exists($installerClass)) {
            $installer = new $installerClass($modulePath);
            return $installer->install();
        } else {
            // Fallback: try to include the file directly
            $installerFile = $modulePath . '/composer-lib/src/Ksfraser/FA_ProductAttributes/Install/ComposerInstaller.php';
            if (file_exists($installerFile)) {
                require_once $installerFile;
                if (class_exists($installerClass)) {
                    $installer = new $installerClass($modulePath);
                    return $installer->install();
                }
            }
        }

        // If we can't load the class, return an error
        return [
            'success' => false,
            'message' => 'Could not load ComposerInstaller class. Make sure the module files are properly installed.',
            'output' => ''
        ];
    }
}
