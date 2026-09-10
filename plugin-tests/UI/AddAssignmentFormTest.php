<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\AddAssignmentForm;
use PHPUnit\Framework\TestCase;

class AddAssignmentFormTest extends TestCase
{
    private function categories(): array
    {
        return [
            ['id' => 1, 'code' => 'size', 'label' => 'Size', 'sort_order' => 3],
            ['id' => 2, 'code' => 'COLOR', 'label' => 'Color', 'sort_order' => 6],
        ];
    }

    private function values(): array
    {
        return [
            ['id' => 1, 'value' => 'Red', 'slug' => 'Red'],
            ['id' => 2, 'value' => 'Blue', 'slug' => 'blues'],
        ];
    }

    public function testRenderEmitsSharedMarkup(): void
    {
        $form = new AddAssignmentForm($this->categories(), 'SKU001');

        $html = $form->render();

        $this->assertStringContainsString('>Add Assignment<', $html);
        $this->assertStringContainsString('name="stock_id" value="SKU001"', $html);
        $this->assertStringContainsString('id="pa_category_select"', $html);
        $this->assertStringContainsString('id="pa_values_box"', $html);
        $this->assertStringContainsString('name="value_ids[]"', $html);
        $this->assertStringContainsString('name="add_all" value="1"', $html);
        $this->assertStringContainsString('name="sort_order"', $html);
        $this->assertStringContainsString('name="add_pa_assignment" value="1"', $html);
        $this->assertStringContainsString('ajax_get_values.php', $html);
        $this->assertStringContainsString('encodeURIComponent(sel.value)', $html);
        $this->assertStringNotContainsString('<form', $html, 'Host form wraps the fieldset');
    }

    public function testRenderPrefillsSelectedCategoryValues(): void
    {
        $form = new AddAssignmentForm($this->categories(), 'SKU001', 2, $this->values());

        $html = $form->render();

        $this->assertStringContainsString('<option value="2" selected', $html);
        $this->assertStringContainsString('id="pa_sort_order" value="6"', $html, 'Sort order inherited from the selected category (Color=6)');
        $this->assertStringContainsString('<input type="checkbox" name="value_ids[]" value="1"> Red (Red)', $html);
        $this->assertStringContainsString('Blue (blues)', $html);
    }

    public function testRenderSortOrderResetsOnCategoryChange(): void
    {
        $form = new AddAssignmentForm($this->categories(), 'SKU001');

        $html = $form->render();

        $this->assertStringContainsString('id="pa_sort_order" value="0"', $html, 'No category preselected -> defaults to 0');
        $this->assertStringContainsString('sortInput.value=paSort[String(sel.value)]||0', $html);
        $this->assertStringContainsString('"2":6', $html, 'Sort map carries category sort orders (Color=6)');
    }

    public function testRenderSupportsAlternativeSubmitConfig(): void
    {
        $form = new AddAssignmentForm(
            $this->categories(),
            'SKU001',
            0,
            [],
            'admin_pa_',
            ['name' => '', 'value' => '', 'label' => 'Add'],
            ['action' => 'add_assignment']
        );

        $html = $form->render();

        $this->assertStringContainsString('id="admin_pa_category_select"', $html);
        $this->assertStringContainsString('id="admin_pa_values_box"', $html);
        $this->assertStringContainsString('name="action" value="add_assignment"', $html);
        $this->assertStringContainsString('>Add</button>', $html);
        $this->assertStringNotContainsString('name="add_pa_assignment"', $html);
    }

    public function testRenderOmitsStockIdWhenEmpty(): void
    {
        $form = new AddAssignmentForm($this->categories(), '');

        $html = $form->render();

        $this->assertStringNotContainsString('name="stock_id"', $html);
    }
}