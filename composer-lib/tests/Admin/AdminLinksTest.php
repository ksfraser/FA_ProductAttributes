<?php

use PHPUnit\Framework\TestCase;

class AdminLinksTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset mock filters before each test
        if (class_exists('FAMock')) {
            FAMock::resetFilters();
        }
    }

    public function testApplyFiltersReturnsOriginalValueWhenNoFiltersRegistered()
    {
        $originalValue = ['core_link'];
        $result = apply_filters('product_attributes_admin_links', $originalValue);

        $this->assertEquals($originalValue, $result);
    }

    public function testAddFilterAndApplyFiltersWorks()
    {
        $callback = function($links) {
            $links[] = 'test_link';
            return $links;
        };

        add_filter('product_attributes_admin_links', $callback);

        $originalValue = ['core_link'];
        $result = apply_filters('product_attributes_admin_links', $originalValue);

        $this->assertEquals(['core_link', 'test_link'], $result);
    }

    public function testMultipleFiltersAreAppliedInOrder()
    {
        $callback1 = function($links) {
            $links[] = 'link1';
            return $links;
        };

        $callback2 = function($links) {
            $links[] = 'link2';
            return $links;
        };

        add_filter('product_attributes_admin_links', $callback1);
        add_filter('product_attributes_admin_links', $callback2);

        $originalValue = ['core_link'];
        $result = apply_filters('product_attributes_admin_links', $originalValue);

        $this->assertEquals(['core_link', 'link1', 'link2'], $result);
    }

    public function testVariationsModuleCanRegisterAdminLink()
    {
        // Simulate the variations module registering its admin link
        $variationsCallback = function($links) {
            $links[] = [
                'name' => 'Product Variations',
                'url' => '/modules/fa_product_attributes_variations/product_variations_admin.php',
                'description' => 'Manage product variations and hierarchies'
            ];
            return $links;
        };

        add_filter('product_attributes_admin_links', $variationsCallback);

        $originalValue = [
            [
                'name' => 'Product Attributes',
                'url' => '/modules/FA_ProductAttributes/product_attributes_admin.php',
                'description' => 'Manage product attributes and categories'
            ]
        ];

        $result = apply_filters('product_attributes_admin_links', $originalValue);

        $this->assertCount(2, $result);
        $this->assertEquals('Product Attributes', $result[0]['name']);
        $this->assertEquals('Product Variations', $result[1]['name']);
    }
}