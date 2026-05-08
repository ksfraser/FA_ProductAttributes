<?php

namespace Ksfraser\FA_ProductAttributes\Test\Hooks;

use PHPUnit\Framework\TestCase;

/**
 * Smoke test: verify module root hooks.php exists and registers expected callbacks.
 */
class ModuleHooksRegistrationTest extends TestCase
{
    public function testHooksPhpRegistersExpectedCallbacks(): void
    {
        $hooksFile = dirname(__DIR__, 2) . '/hooks.php';

        // Force fa_hooks() to return a tracking manager in this test.
        $GLOBALS['mock_fa_hooks'] = new class {
            private $filters = [];
            private $actions = [];

            public function add_filter($name, $callback, $priority = 10)
            {
                if (!isset($this->filters[$name])) {
                    $this->filters[$name] = [];
                }
                if (!isset($this->filters[$name][$priority])) {
                    $this->filters[$name][$priority] = [];
                }
                $this->filters[$name][$priority][] = $callback;
            }

            public function add_action($name, $callback, $priority = 10)
            {
                if (!isset($this->actions[$name])) {
                    $this->actions[$name] = [];
                }
                if (!isset($this->actions[$name][$priority])) {
                    $this->actions[$name][$priority] = [];
                }
                $this->actions[$name][$priority][] = $callback;
            }

            public function has_filter($name, $callback)
            {
                if (!isset($this->filters[$name])) {
                    return false;
                }
                foreach ($this->filters[$name] as $callbacks) {
                    foreach ($callbacks as $registered) {
                        if ($registered === $callback) {
                            return true;
                        }
                    }
                }
                return false;
            }

            public function has_filter_method($name, $method)
            {
                if (!isset($this->filters[$name])) {
                    return false;
                }
                foreach ($this->filters[$name] as $callbacks) {
                    foreach ($callbacks as $registered) {
                        if (is_array($registered) && isset($registered[1]) && $registered[1] === $method) {
                            return true;
                        }
                    }
                }
                return false;
            }

            public function has_action($name, $callback)
            {
                if (!isset($this->actions[$name])) {
                    return false;
                }
                foreach ($this->actions[$name] as $callbacks) {
                    foreach ($callbacks as $registered) {
                        if ($registered === $callback) {
                            return true;
                        }
                    }
                }
                return false;
            }

            public function has_action_method($name, $method)
            {
                if (!isset($this->actions[$name])) {
                    return false;
                }
                foreach ($this->actions[$name] as $callbacks) {
                    foreach ($callbacks as $registered) {
                        if (is_array($registered) && isset($registered[1]) && $registered[1] === $method) {
                            return true;
                        }
                    }
                }
                return false;
            }
        };

        $this->assertFileExists($hooksFile, 'Expected module-level hooks.php entrypoint');

        require_once $hooksFile;

        $this->assertTrue(
            defined('FA_PRODUCT_ATTRIBUTES_HOOKS_LOADED'),
            'hooks.php should define FA_PRODUCT_ATTRIBUTES_HOOKS_LOADED guard constant'
        );

        $this->assertTrue(function_exists('fa_hooks'), 'fa_hooks() must be available in test runtime');
        $this->assertTrue(class_exists('hooks_FA_ProductAttributes'));
        $moduleHooks = new \hooks_FA_ProductAttributes();
        $this->assertTrue($moduleHooks->activate());

        $this->assertTrue(method_exists($moduleHooks, 'install_options'));
        $this->assertTrue(method_exists($moduleHooks, 'install_access'));
        $this->assertTrue(method_exists($moduleHooks, 'item_display_tab_headers'));
        $this->assertTrue(method_exists($moduleHooks, 'item_display_tab_content'));
        $this->assertTrue(method_exists($moduleHooks, 'pre_item_write'));
        $this->assertTrue(method_exists($moduleHooks, 'pre_item_delete'));

        $hooks = fa_hooks();

        $this->assertTrue($hooks->has_filter_method('item_display_tab_headers', 'item_display_tab_headers'));
        $this->assertTrue($hooks->has_filter_method('item_display_tab_content', 'item_display_tab_content'));
        $this->assertTrue($hooks->has_filter_method('pre_item_write', 'pre_item_write'));
        $this->assertTrue($hooks->has_action_method('pre_item_delete', 'pre_item_delete'));
    }
}
