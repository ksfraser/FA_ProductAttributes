<?php

/**
 * Content-equivalence tests for the admin pages rebuilt on the reusable
 * ksf_FA_Common MasterSummaryTable component.
 *
 * Each scenario renders the page in a fresh PHP process (see
 * render-admin-page.php) against the FAMock DB fixture layer, then asserts
 * that every original hard-coded element is still present (titles, per-tab
 * tables, DDLs, forms, empty states) and that the MasterSummaryTable markers
 * (tablestyle, record_id/_tabs_sel hidden fields, Edit/Delete row actions)
 * are present, with no legacy `tablestyle2` tables remaining.
 *
 * @package FA_ProductAttributes
 */

namespace Ksfraser\FA_ProductAttributes\Test\Admin;

use PHPUnit\Framework\TestCase;

class AdminPagesEquivalenceTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private const CATEGORIES = [
        ['id' => 1, 'code' => 'color', 'label' => 'Color', 'description' => '', 'sort_order' => 2, 'active' => 1],
        ['id' => 2, 'code' => 'size', 'label' => 'Size', 'description' => '', 'sort_order' => 1, 'active' => 0],
    ];

    /** @var array<int, array<string, mixed>> */
    private const VALUES = [
        ['id' => 11, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 1, 'active' => 1],
        ['id' => 12, 'category_id' => 1, 'value' => 'Blue', 'slug' => 'blue', 'sort_order' => 2, 'active' => 0],
    ];

    /** @var array<int, array<string, mixed>> */
    private const STOCK_ITEMS = [
        ['stock_id' => 'SKU-100', 'description' => 'Organic Coffee'],
        ['stock_id' => 'SKU-200', 'description' => 'Green Tea'],
    ];

    /** @var array<int, array<string, mixed>> */
    private const ASSIGNMENTS = [
        [
            'id' => 31, 'stock_id' => 'SKU-100', 'category_id' => 1, 'value_id' => 11,
            'sort_order' => 1, 'is_default' => 0, 'parent_stock_id' => null,
            'category_code' => 'color', 'category_label' => 'Color', 'category_sort_order' => 2,
            'value_label' => 'Red', 'value_slug' => 'red',
        ],
    ];

    /** @var array<int, array<string, mixed>> */
    private const FLAGS = [
        ['id' => 1, 'code' => 'is_organic', 'label' => 'Organic Certified', 'sort_order' => 1, 'active' => 1],
        ['id' => 2, 'code' => 'is_gluten_free', 'label' => 'Gluten Free', 'sort_order' => 2, 'active' => 0],
    ];

    /** @var array<int, array<string, mixed>> */
    private const BRANDS = [
        ['id' => 5, 'name' => 'Acme'],
        ['id' => 6, 'name' => 'Beta'],
    ];

    /**
     * Render a page in a fresh process and return its HTML.
     *
     * @param array<string, mixed> $args Harness args (page/get/post/fixtures)
     * @return string Rendered HTML
     */
    private function renderPage(array $args): string
    {
        $cmd = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(__DIR__ . '/render-admin-page.php')
            . ' ' . escapeshellarg((string) json_encode($args));

        $output = [];
        $code   = 0;
        exec($cmd, $output, $code);

        $this->assertSame(0, $code, 'render harness exited non-zero');
        $decoded = json_decode(implode("\n", $output), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('html', $decoded);

        return (string) $decoded['html'];
    }

    // ── index.php ────────────────────────────────────────────────────────────

    public function testIndexCategoriesTabPreservesOriginalElements(): void
    {
        $html = $this->renderPage([
            'page' => 'index',
            'get'  => ['tab' => 'categories'],
            'fixtures' => [
                'FROM `0_product_attribute_categories`' => self::CATEGORIES,
            ],
        ]);

        $this->assertStringContainsString('<h1>Product Attributes</h1>', $html);
        $this->assertStringContainsString('?tab=categories', $html);
        $this->assertStringContainsString('?tab=values', $html);
        $this->assertStringContainsString('?tab=assignments', $html);

        foreach (['Code', 'Label', 'Sort', 'Active'] as $header) {
            $this->assertStringContainsString('<th>' . $header . '</th>', $html);
        }
        $this->assertStringContainsString('color', $html);
        $this->assertStringContainsString('Color', $html);
        $this->assertStringContainsString('size', $html);
        $this->assertStringContainsString('Size', $html);

        $this->assertStringContainsString('value="upsert_category"', $html);
        $this->assertStringContainsString('name="code" required placeholder="size_alpha"', $html);
        $this->assertStringContainsString('name="label" required placeholder="Size (alpha)"', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('name="sort_order"', $html);
        $this->assertStringContainsString('name="active"', $html);
        $this->assertStringContainsString('Add Category', $html);

        $this->assertStringContainsString('<table class="tablestyle">', $html);
        $this->assertStringContainsString('name="edit_1"', $html);
        $this->assertStringContainsString('name="delete_2"', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="categories"', $html);
        $this->assertStringContainsString('name="id" value=""', $html);
        $this->assertStringNotContainsString('tablestyle2', $html);
    }

    public function testIndexValuesTabPreservesOriginalElements(): void
    {
        $html = $this->renderPage([
            'page' => 'index',
            'get'  => ['tab' => 'values', 'category_id' => 1],
            'fixtures' => [
                'FROM `0_product_attribute_categories`' => self::CATEGORIES,
                'FROM `0_product_attribute_values` WHERE category_id = 1' => self::VALUES,
            ],
        ]);

        $this->assertStringContainsString('<select name="category_id" onchange="this.form.submit()">', $html);
        $this->assertStringContainsString('color', $html);
        $this->assertStringContainsString('size', $html);

        foreach (['Value', 'Slug', 'Sort', 'Active'] as $header) {
            $this->assertStringContainsString('<th>' . $header . '</th>', $html);
        }
        $this->assertStringContainsString('Red', $html);
        $this->assertStringContainsString('red', $html);
        $this->assertStringContainsString('Blue', $html);
        $this->assertStringContainsString('blue', $html);

        $this->assertStringContainsString('value="upsert_value"', $html);
        $this->assertStringContainsString('name="category_id" value="1"', $html);
        $this->assertStringContainsString('name="value" required placeholder="Red"', $html);
        $this->assertStringContainsString('name="slug" required placeholder="red"', $html);
        $this->assertStringContainsString('Add Value', $html);

        $this->assertStringContainsString('name="edit_11"', $html);
        $this->assertStringContainsString('name="delete_12"', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="values"', $html);
        $this->assertStringNotContainsString('tablestyle2', $html);
    }

    public function testIndexAssignmentsTabWithStockPreservesOriginalElements(): void
    {
        $html = $this->renderPage([
            'page' => 'index',
            'get'  => ['tab' => 'assignments', 'stock_id' => 'SKU-100', 'category_id' => 1],
            'fixtures' => [
                'FROM `0_product_attribute_categories`' => self::CATEGORIES,
                'FROM `0_product_attribute_values` WHERE category_id = 1' => self::VALUES,
                'FROM `0_stock_master`' => self::STOCK_ITEMS,
                'FROM `0_product_attribute_assignments`' => self::ASSIGNMENTS,
            ],
        ]);

        $this->assertStringContainsString('<h2>Assignments</h2>', $html);
        $this->assertStringContainsString('<select name="stock_id">', $html);
        $this->assertStringContainsString('-- Select Stock Item --', $html);
        $this->assertStringContainsString('value="SKU-100"', $html);
        $this->assertStringContainsString('Organic Coffee', $html);
        $this->assertStringContainsString('Green Tea', $html);
        $this->assertStringContainsString('Load', $html);

        foreach (['Category', 'Value', 'Slug', 'Sort'] as $header) {
            $this->assertStringContainsString('<th>' . $header . '</th>', $html);
        }
        $this->assertStringContainsString('color', $html);
        $this->assertStringContainsString('Red', $html);
        $this->assertStringContainsString('red', $html);

        $this->assertStringContainsString('value="add_assignment"', $html);
        $this->assertStringContainsString('<input type="hidden" name="stock_id" value="SKU-100" />', $html);
        $this->assertStringContainsString('Red (red)', $html);
        $this->assertStringContainsString('Add', $html);

        $this->assertStringContainsString('name="delete_31"', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="assignments"', $html);
        $this->assertStringNotContainsString('Enter a Stock ID to view/add assignments.', $html);
        $this->assertStringNotContainsString('tablestyle2', $html);
    }

    public function testIndexAssignmentsTabWithoutStockShowsHint(): void
    {
        $html = $this->renderPage([
            'page' => 'index',
            'get'  => ['tab' => 'assignments'],
            'fixtures' => [
                'FROM `0_product_attribute_categories`' => self::CATEGORIES,
            ],
        ]);

        $this->assertStringContainsString('<h2>Assignments</h2>', $html);
        $this->assertStringContainsString('<select name="stock_id">', $html);
        $this->assertStringContainsString('Enter a Stock ID to view/add assignments.', $html);
        $this->assertStringNotContainsString('value="add_assignment"', $html);
        $this->assertStringNotContainsString('name="delete_', $html);
        $this->assertStringNotContainsString('tablestyle2', $html);
    }

    public function testIndexCategoriesEditPrefillsForm(): void
    {
        $html = $this->renderPage([
            'page' => 'index',
            'get'  => ['tab' => 'categories', 'edit_id' => 2],
            'fixtures' => [
                'FROM `0_product_attribute_categories`' => self::CATEGORIES,
            ],
        ]);

        $this->assertStringContainsString('Edit Category', $html);
        $this->assertStringContainsString('name="id" value="2"', $html);
        $this->assertStringContainsString('value="size"', $html);
        $this->assertStringContainsString('value="Size"', $html);
        $this->assertStringContainsString('?tab=categories', $html);
    }

    // ── lifecycle-flags.php ──────────────────────────────────────────────────

    public function testLifecycleFlagsPreservesOriginalElements(): void
    {
        $html = $this->renderPage([
            'page' => 'lifecycle-flags',
            'get'  => [],
            'fixtures' => [
                'FROM `0_product_lifecycle_flag_defs`' => self::FLAGS,
            ],
        ]);

        $this->assertStringContainsString('<h1>Lifecycle Flag Definitions</h1>', $html);
        $this->assertStringContainsString(
            'Manage the storefront flags that appear as checkboxes on the product lifecycle tab.',
            $html
        );

        foreach (['Code', 'Label', 'Sort', 'Active'] as $header) {
            $this->assertStringContainsString('<th>' . $header . '</th>', $html);
        }
        $this->assertStringContainsString('is_organic', $html);
        $this->assertStringContainsString('Organic Certified', $html);
        $this->assertStringContainsString('is_gluten_free', $html);
        $this->assertStringContainsString('Gluten Free', $html);
        $this->assertStringContainsString('Yes', $html);
        $this->assertStringContainsString('No', $html);

        $this->assertStringContainsString('value="add_flag"', $html);
        $this->assertStringContainsString('name="code" required placeholder="is_organic" pattern="[a-z_]+"', $html);
        $this->assertStringContainsString('name="label" required placeholder="Organic Certified"', $html);
        $this->assertStringContainsString('name="sort_order"', $html);
        $this->assertStringContainsString('name="active"', $html);
        $this->assertStringContainsString('Save Flag', $html);

        $this->assertStringContainsString('name="edit_1"', $html);
        $this->assertStringContainsString('name="delete_2"', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="flags"', $html);
        $this->assertStringNotContainsString('No flags defined yet.', $html);
        $this->assertStringNotContainsString('tablestyle2', $html);
    }

    public function testLifecycleFlagsEmptyStatePreserved(): void
    {
        $html = $this->renderPage([
            'page' => 'lifecycle-flags',
            'get'  => [],
            'fixtures' => [],
        ]);

        $this->assertStringContainsString('No flags defined yet.', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="flags"', $html);
    }

    public function testLifecycleFlagsEditPrefillsForm(): void
    {
        $html = $this->renderPage([
            'page' => 'lifecycle-flags',
            'get'  => ['edit_id' => 1],
            'fixtures' => [
                'FROM `0_product_lifecycle_flag_defs`' => self::FLAGS,
            ],
        ]);

        $this->assertStringContainsString('Edit Flag', $html);
        $this->assertStringContainsString('name="flag_id" value="1"', $html);
        $this->assertStringContainsString('value="is_organic"', $html);
        $this->assertStringContainsString('value="Organic Certified"', $html);
        $this->assertStringContainsString('?tab=flags', $html);
    }

    // ── brands.php ───────────────────────────────────────────────────────────

    public function testBrandsBrandTypePreservesOriginalElements(): void
    {
        $html = $this->renderPage([
            'page' => 'brands',
            'get'  => ['type' => 'brand'],
            'fixtures' => [
                'WHERE type = brand' => self::BRANDS,
            ],
        ]);

        $this->assertStringContainsString('<h1>Brand / Manufacturer Management</h1>', $html);
        $this->assertStringContainsString(
            'Manage the dropdown values that appear in the Product Identifiers tab.',
            $html
        );
        $this->assertStringContainsString('?type=brand" class="active">Brand', $html);
        $this->assertStringContainsString('?type=manufacturer', $html);

        $this->assertStringContainsString('<th>#</th>', $html);
        $this->assertStringContainsString('<th>Name</th>', $html);
        $this->assertStringContainsString('Acme', $html);
        $this->assertStringContainsString('Beta', $html);

        $this->assertStringContainsString('value="add_entry"', $html);
        $this->assertStringContainsString('name="entry_type" value="brand"', $html);
        $this->assertStringContainsString('name="name" required maxlength="128"', $html);
        $this->assertStringContainsString('Add Brand', $html);

        $this->assertStringContainsString('name="edit_5"', $html);
        $this->assertStringContainsString('name="delete_6"', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="brand"', $html);
        $this->assertStringNotContainsString('No entries defined yet.', $html);
        $this->assertStringNotContainsString('tablestyle2', $html);
    }

    public function testBrandsManufacturerTypeEmptyStatePreserved(): void
    {
        $html = $this->renderPage([
            'page' => 'brands',
            'get'  => ['type' => 'manufacturer'],
            'fixtures' => [
                'WHERE type = manufacturer' => [],
            ],
        ]);

        $this->assertStringContainsString('?type=manufacturer" class="active">Manufacturer', $html);
        $this->assertStringContainsString('No entries defined yet.', $html);
        $this->assertStringContainsString('name="_tabs_sel" value="manufacturer"', $html);
    }

    public function testBrandsEditPrefillsForm(): void
    {
        $html = $this->renderPage([
            'page' => 'brands',
            'get'  => ['type' => 'brand', 'edit_id' => 5],
            'fixtures' => [
                'WHERE type = brand' => self::BRANDS,
            ],
        ]);

        $this->assertStringContainsString('Edit Brand', $html);
        $this->assertStringContainsString('name="entry_id" value="5"', $html);
        $this->assertStringContainsString('value="Acme"', $html);
        $this->assertStringContainsString('Save Brand', $html);
    }
}
