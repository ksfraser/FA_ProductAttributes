<?php

namespace Ksfraser\FA_ProductAttributes\Plugin;

/**
 * Single Responsibility: Loads plugin extensions for the FA_ProductAttributes module on-demand.
 *
 * Implemented as a Singleton so that plugin registrations are shared across the request lifecycle.
 */
class PluginLoader
{
    /** @var static|null */
    private static $instance = null;

    /** @var bool */
    private $loaded = false;

    /**
     * Private constructor — use {@see getInstance()}.
     */
    private function __construct()
    {
    }

    /**
     * Return the shared singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Discover and load plugin extension files if not already loaded.
     *
     * Plugins should place a file named `hooks.php` inside a directory that begins with
     * `fa_product_attributes_` relative to the FrontAccounting modules directory
     * (`$path_to_root/modules/`).
     */
    public function loadPluginsOnDemand(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        global $path_to_root;

        if (empty($path_to_root)) {
            return;
        }

        $modulesDir = rtrim((string)$path_to_root, '/') . '/modules/';

        if (!is_dir($modulesDir)) {
            return;
        }

        $entries = scandir($modulesDir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (strpos($entry, 'fa_product_attributes_') !== 0) {
                continue;
            }

            $hooksFile = $modulesDir . $entry . '/hooks.php';
            if (file_exists($hooksFile)) {
                /** @noinspection PhpIncludeInspection */
                require_once $hooksFile;
            }
        }
    }
}
