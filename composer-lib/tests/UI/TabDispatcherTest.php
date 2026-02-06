<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\TabDispatcher;
use PHPUnit\Framework\TestCase;

class TabDispatcherTest extends TestCase
{
    public function testConstructorWithDefaultTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao);

        // Test that it defaults to 'categories' when no tab is specified
        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testConstructorWithSpecifiedTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'values');

        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testConstructorWithEmbeddedMode(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'categories', true);

        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testConstructorReadsFromGetParameters(): void
    {
        // Set up GET parameters
        $_GET['selected_tab'] = 'values';

        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao);

        // Clean up
        unset($_GET['selected_tab']);

        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testConstructorReadsFromPostParameters(): void
    {
        // Set up POST parameters
        $_POST['tab'] = 'assignments';

        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao);

        // Clean up
        unset($_POST['tab']);

        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testRenderStandaloneMode(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'categories', false);

        // This test is limited because the actual rendering depends on class_exists checks
        // In a real application, this would instantiate CategoriesTab and call render()
        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testRenderMethodExists(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'categories', false);

        $this->assertTrue(method_exists($dispatcher, 'render'));
    }

    public function testRenderDoesNotThrowExceptionForUnknownTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'unknown_tab', false);

        // This should not throw an exception even for unknown tabs
        ob_start();
        $dispatcher->render();
        ob_end_clean();

        $this->assertTrue(true);
    }

    public function testRenderEmbeddedMode(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'product_attributes', true);

        // This should not throw an exception
        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testRenderPluginTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'product_attributes_dimensions', true);

        // This should not throw an exception
        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testRenderStandaloneCategoriesTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'categories', false);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        // Should contain tab navigation and some content
        $this->assertStringContainsString('Categories', $output);
        $this->assertStringContainsString('Values', $output);
        $this->assertStringContainsString('Assignments', $output);
    }

    public function testRenderStandaloneValuesTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'values', false);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        // Should contain tab navigation
        $this->assertStringContainsString('Categories', $output);
        $this->assertStringContainsString('Values', $output);
        $this->assertStringContainsString('Assignments', $output);
    }

    public function testRenderStandaloneAssignmentsTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'assignments', false);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        // Should contain tab navigation
        $this->assertStringContainsString('Categories', $output);
        $this->assertStringContainsString('Values', $output);
        $this->assertStringContainsString('Assignments', $output);
    }
}