<?php

namespace Ksfraser\FA_ProductAttributes\Plugin;

class PluginLoader
{
    private static $instance = null;
    private $plugins_loaded = false;

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load plugins on demand when core functionality is accessed
     */
    public function loadPluginsOnDemand(): void
    {
        if ($this->plugins_loaded) {
            return;
        }

        global $path_to_root;
        $plugins_dir = $path_to_root . '/modules';

        // Discover and load plugins
        $plugin_dirs = glob($plugins_dir . '/FA_ProductAttributes_*', GLOB_ONLYDIR);

        foreach ($plugin_dirs as $plugin_dir) {
            $plugin_name = basename($plugin_dir);
            $hooks_file = $plugin_dir . '/hooks.php';

            if (file_exists($hooks_file)) {
                try {
                    // Include the plugin hooks file
                    require_once $hooks_file;

                    // Instantiate the plugin hooks class
                    $class_name = 'hooks_' . $plugin_name;
                    if (class_exists($class_name)) {
                        $plugin_hooks = new $class_name();

                        // Call the plugin's register_hooks method if it exists
                        if (method_exists($plugin_hooks, 'register_hooks')) {
                            $plugin_hooks->register_hooks();
                        }
                    }
                } catch (\Exception $e) {
                    error_log("FA_ProductAttributes: Failed to load plugin {$plugin_name}: " . $e->getMessage());
                }
            }
        }

        $this->plugins_loaded = true;
    }
}