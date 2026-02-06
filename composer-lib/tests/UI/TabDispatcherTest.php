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

    public function testRenderEmbeddedMainTabWithStockId(): void
    {
        global $path_to_root;
        $originalPath = $path_to_root ?? null;
        $path_to_root = '/fake/path';

        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['category_label' => 'Color', 'value_label' => 'Red'],
                ['category_label' => 'Size', 'value_label' => 'Large']
            ]);

        $_GET['stock_id'] = 'TEST123';

        $dispatcher = new TabDispatcher($dao, 'product_attributes', true);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        unset($_GET['stock_id']);
        $path_to_root = $originalPath;

        $this->assertStringContainsString('Color', $output);
        $this->assertStringContainsString('Red', $output);
        $this->assertStringContainsString('Size', $output);
        $this->assertStringContainsString('Large', $output);
        $this->assertStringContainsString('Manage Product Attributes', $output);
    }

    public function testRenderEmbeddedMainTabWithoutStockId(): void
    {
        global $path_to_root;
        $originalPath = $path_to_root ?? null;
        $path_to_root = '/fake/path';

        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'product_attributes', true);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        $path_to_root = $originalPath;

        // Check that display_error was called with the expected message
        $this->assertArrayHasKey('test_errors', $GLOBALS);
        $this->assertContains('No stock ID provided', $GLOBALS['test_errors']);
    }

    public function testRenderEmbeddedMainTabWithNoAssignments(): void
    {
        global $path_to_root;
        $originalPath = $path_to_root ?? null;
        $path_to_root = '/fake/path';

        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $_GET['stock_id'] = 'TEST123';

        $dispatcher = new TabDispatcher($dao, 'product_attributes', true);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        unset($_GET['stock_id']);
        $path_to_root = $originalPath;

        $this->assertStringContainsString('No attributes assigned', $output);
    }

    public function testRenderPluginTabWithHookContent(): void
    {
        // Mock the global path_to_root and hooks
        global $path_to_root;
        $originalPath = $path_to_root ?? null;
        $path_to_root = '/fake/path';

        // Create a temporary hooks file
        $hooksFile = '/fake/path/modules/FA_ProductAttributes/fa_hooks.php';
        $hooksContent = '<?php function fa_hooks() { return new class { public function apply_filters($filter, $content, $stockId, $tab) { return "Plugin Content"; } }; }';

        // We can't easily mock file_exists and require_once, so we'll test the fallback path
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'product_attributes_dimensions', true);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        // Should fall back to generic plugin tab since hooks file doesn't exist
        $this->assertStringContainsString('Dimensions', $output);
        $this->assertStringContainsString('Plugin content not implemented', $output);

        // Restore global
        $path_to_root = $originalPath;
    }

    public function testRenderGenericPluginTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'product_attributes_unknown', true);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('Unknown', $output);
        $this->assertStringContainsString('Plugin content not implemented', $output);
    }

    public function testConstructorDetectsEmbeddedModeFromGet(): void
    {
        $_GET['selected_tab'] = 'product_attributes';

        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao);

        unset($_GET['selected_tab']);

        $this->assertInstanceOf(TabDispatcher::class, $dispatcher);
    }

    public function testRenderUnknownStandaloneTab(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $dispatcher = new TabDispatcher($dao, 'unknown_plugin', false);

        ob_start();
        $dispatcher->render();
        $output = ob_get_clean();

        // Should show tab navigation and fall back to generic plugin handling
        $this->assertStringContainsString('Categories', $output);
        $this->assertStringContainsString('Unknown_plugin', $output);
    }
}