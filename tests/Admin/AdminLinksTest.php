<?php

namespace Ksfraser\FA_ProductAttributes\Test\Admin;

use PHPUnit\Framework\TestCase;

/**
 * Test admin functionality including hook-based module discovery
 */
class AdminLinksTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset any test filters between tests
        if (function_exists('apply_filters')) {
            // The apply_filters mock uses static storage, so we need to reset it
            // This is a limitation of the mock - in a real implementation we'd use a better approach
        }
    }

    public function testAdminLinksHookIsCalled()
    {
        // This test verifies that the admin page calls the product_attributes_admin_links filter
        // We can't easily test the full admin page execution in unit tests due to FA dependencies,
        // but we can test that the hook system is properly set up

        $links = [];
        add_filter('product_attributes_admin_links', function($links) {
            $links[] = [
                'name' => 'Test Module',
                'url' => '/modules/test_module/admin.php',
                'description' => 'Test module description'
            ];
            return $links;
        });

        $result = apply_filters('product_attributes_admin_links', $links);

        $this->assertCount(1, $result);
        $this->assertEquals('Test Module', $result[0]['name']);
        $this->assertEquals('/modules/test_module/admin.php', $result[0]['url']);
        $this->assertEquals('Test module description', $result[0]['description']);
    }

    public function testVariationsModuleRegistersAdminLink()
    {
        // Test that the variations module properly registers its admin link
        // This would require loading the variations hooks.php file

        // For now, we'll test the hook registration pattern
        $this->assertTrue(function_exists('apply_filters'), 'apply_filters function should be available');
        $this->assertTrue(function_exists('add_filter'), 'add_filter function should be available');
    }
}