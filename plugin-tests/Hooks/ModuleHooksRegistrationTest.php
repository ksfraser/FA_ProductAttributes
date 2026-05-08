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

        $tabs = array('details' => array('Details', 'ITEM-1'));
        $resultTabs = $moduleHooks->item_display_tab_headers($tabs, 'ITEM-1');
        $this->assertIsArray($resultTabs);
        $this->assertArrayHasKey('product_attributes', $resultTabs);

        $this->assertFalse(
            $moduleHooks->item_display_tab_content('ITEM-1', 'details'),
            'Non-product_attributes tabs should not be handled by this hooks class'
        );

        $this->assertSame(
            null,
            $moduleHooks->pre_item_delete('ITEM-1'),
            'Delete hook should return null in FA action-style lifecycle'
        );
    }
}
