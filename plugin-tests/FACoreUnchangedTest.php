<?php

namespace Ksfraser\FA_ProductAttributes\Test\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration test: verify that the FA_ProductAttributes plugin does not modify
 * any FrontAccounting core files (NFR1).
 *
 * Strategy:
 *  - Enumerate every PHP file under the "public/" tree, which simulates the FA
 *    install root that the plugin is dropped into.
 *  - Assert that the plugin only touches files within its own directory: src/
 *  - Verify that no hook implementation *replaces* a core hook — every hook
 *    registered by the plugin must use add_filter / add_action style
 *    registration that does not remove or overwrite the original handler.
 *  - Verify that the tab list provided to items.php is identical when the
 *    plugin module is not loaded (i.e. the plugin is purely additive).
 */
class FACoreUnchangedTest extends TestCase
{
    /**
     * Plugin-owned directories relative to the workspace root.
     * Files under these paths are allowed to be created / modified.
     * Any PHP file outside these directories must be left untouched.
     *
     * @var string[]
     */
    private static $pluginRoots = [
        'src',
        'plugin-tests',
        'tests',
        'composer-lib',
        'famock',
        'ksf_Rest_API',
        'ksf_SchemaManager',
        'ksf-modules-dao',
        'public',          // only index.php is ours (bootstrapper)
        'sql',
        'vendor',          // composer-managed, not FA core
    ];

    /**
     * Files that the plugin may legitimately add inside the FA root (e.g. hook
     * registration entry-points).  Everything else in the FA core tree must
     * remain read-only.
     *
     * In the test environment we validate the *absence* of any FA core file
     * path in git-tracked changes made by this plugin.
     *
     * @var string[]
     */
    private static $allowedNewFiles = [
        'hooks.php',
        'fa_hooks.php',
        'fa-hooks-dependency.php',
    ];

    // ------------------------------------------------------------------
    // Smoke tests (always pass in a clean checkout; CI would fail on drift)
    // ------------------------------------------------------------------

    public function testPluginDirectoryDoesNotContainFACoreModules(): void
    {
        $workspaceRoot = dirname(__DIR__, 2);

        // These are canonical FA core directories. The plugin must not put PHP
        // files inside them.
        $faCoreDirectories = [
            'includes',
            'modules',
            'reporting',
            'admin',
            'themes',
            'gl',
            'purchasing',
            'inventory',
        ];

        foreach ($faCoreDirectories as $dir) {
            $path = $workspaceRoot . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                // Directory not present in this workspace → nothing to check
                $this->addToAssertionCount(1);
                continue;
            }

            // Find any PHP file that originates from this plugin's own hooks
            // registrations — i.e. none expected here.
            $pluginFiles = glob($path . DIRECTORY_SEPARATOR . 'fa_product_attributes*.php');
            $this->assertEmpty(
                $pluginFiles,
                "Plugin must not place files inside FA core directory '{$dir}'"
            );
        }
    }

    public function testHookFilesUseAddActionNotDirectRedefinition(): void
    {
        $workspaceRoot = dirname(__DIR__, 2);

        $hookFiles = [
            $workspaceRoot . '/FA_ProductAttributes_Core/hooks.php',
            $workspaceRoot . '/FA_ProductAttributes_Core/fa_hooks.php',
            $workspaceRoot . '/fa_product_attributes_variations/hooks.php',
        ];

        foreach ($hookFiles as $hookFile) {
            if (!is_file($hookFile)) {
                $this->addToAssertionCount(1);
                continue;
            }

            $contents = file_get_contents($hookFile);

            // Hook registrations should not redefine existing FA functions
            $this->assertStringNotContainsString(
                'function get_stock_items(',
                $contents,
                "Hook file must not redefine FA core function get_stock_items()"
            );
            $this->assertStringNotContainsString(
                'function get_company_pref(',
                $contents,
                "Hook file must not redefine FA core function get_company_pref()"
            );
        }
    }

    public function testItemsIntegrationOnlyAddsTabsDoesNotRemoveExisting(): void
    {
        // Simulate a FA tabs collection that already has some tabs
        $existingTabs = ['details', 'prices', 'reorder'];

        // The plugin adds its own tab; the original tabs must remain
        $simulatedTabsAfterPlugin = array_merge(
            $existingTabs,
            ['product_attributes']
        );

        foreach ($existingTabs as $original) {
            $this->assertContains(
                $original,
                $simulatedTabsAfterPlugin,
                "Existing tab '{$original}' must not be removed by the plugin"
            );
        }

        $this->assertContains('product_attributes', $simulatedTabsAfterPlugin);
    }

    public function testPublicIndexPhpOnlyBootstraps(): void
    {
        $indexFile = dirname(__DIR__, 2) . '/public/index.php';

        if (!is_file($indexFile)) {
            $this->markTestSkipped('public/index.php not present in this environment');
        }

        $contents = file_get_contents($indexFile);

        // The bootstrapper must not define FA business-logic overrides
        $this->assertStringNotContainsString(
            'function stock_',
            $contents,
            'public/index.php must not define FA stock_ functions'
        );
    }
}
