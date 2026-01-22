<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\RoyalOrderHelper;
use PHPUnit\Framework\TestCase;

class RoyalOrderHelperTest extends TestCase
{
    public function testGetRoyalOrderOptionsReturnsCorrectStructure(): void
    {
        $options = RoyalOrderHelper::getRoyalOrderOptions();

        $this->assertIsArray($options);
        $this->assertCount(9, $options);

        // Check that keys are 1-9 and values are strings
        for ($i = 1; $i <= 9; $i++) {
            $this->assertArrayHasKey($i, $options);
            $this->assertIsString($options[$i]);
            $this->assertNotEmpty($options[$i]);
        }
    }

    public function testGetRoyalOrderOptionsContainsExpectedValues(): void
    {
        $options = RoyalOrderHelper::getRoyalOrderOptions();

        $expectedLabels = [
            1 => 'Quantity',
            2 => 'Opinion',
            3 => 'Size',
            4 => 'Age',
            5 => 'Shape',
            6 => 'Color',
            7 => 'Proper adjective',
            8 => 'Material',
            9 => 'Purpose'
        ];

        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->assertEquals($expectedLabel, $options[$key]);
        }
    }

    public function testGetRoyalOrderLabelReturnsCorrectLabel(): void
    {
        $this->assertEquals('Quantity', RoyalOrderHelper::getRoyalOrderLabel(1));
        $this->assertEquals('Opinion', RoyalOrderHelper::getRoyalOrderLabel(2));
        $this->assertEquals('Size', RoyalOrderHelper::getRoyalOrderLabel(3));
        $this->assertEquals('Color', RoyalOrderHelper::getRoyalOrderLabel(6));
        $this->assertEquals('Purpose', RoyalOrderHelper::getRoyalOrderLabel(9));
    }

    public function testGetRoyalOrderLabelReturnsNullForInvalidInput(): void
    {
        $this->assertNull(RoyalOrderHelper::getRoyalOrderLabel(0));
        $this->assertNull(RoyalOrderHelper::getRoyalOrderLabel(10));
        $this->assertNull(RoyalOrderHelper::getRoyalOrderLabel(-1));
        $this->assertNull(RoyalOrderHelper::getRoyalOrderLabel(100));
    }

    public function testGenerateDropdownHtmlCreatesValidSelectElement(): void
    {
        $html = RoyalOrderHelper::generateDropdownHtml('sort_order', 3);

        $this->assertStringStartsWith('<select name="sort_order">', $html);
        $this->assertStringEndsWith('</select>', $html);
        $this->assertStringContainsString('value="3" selected', $html);
        $this->assertStringContainsString('3 - Size', $html);
    }

    public function testGenerateDropdownHtmlWithAttributes(): void
    {
        $attributes = ['class' => 'form-control', 'id' => 'sort_order_select'];
        $html = RoyalOrderHelper::generateDropdownHtml('sort_order', 0, $attributes);

        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('id="sort_order_select"', $html);
    }

    public function testGenerateDropdownHtmlNoSelection(): void
    {
        $html = RoyalOrderHelper::generateDropdownHtml('sort_order', 0);

        $this->assertStringNotContainsString('selected', $html);
    }

    public function testIsValidSortOrder(): void
    {
        $this->assertTrue(RoyalOrderHelper::isValidSortOrder(1));
        $this->assertTrue(RoyalOrderHelper::isValidSortOrder(5));
        $this->assertTrue(RoyalOrderHelper::isValidSortOrder(9));

        $this->assertFalse(RoyalOrderHelper::isValidSortOrder(0));
        $this->assertFalse(RoyalOrderHelper::isValidSortOrder(10));
        $this->assertFalse(RoyalOrderHelper::isValidSortOrder(-1));
    }

    public function testGetMinSortOrder(): void
    {
        $this->assertEquals(1, RoyalOrderHelper::getMinSortOrder());
    }

    public function testGetMaxSortOrder(): void
    {
        $this->assertEquals(9, RoyalOrderHelper::getMaxSortOrder());
    }

    public function testDropdownContainsAllOptions(): void
    {
        $html = RoyalOrderHelper::generateDropdownHtml('sort_order');

        for ($i = 1; $i <= 9; $i++) {
            $this->assertStringContainsString('value="' . $i . '"', $html);
        }
    }

    public function testDropdownOptionsAreProperlyFormatted(): void
    {
        $html = RoyalOrderHelper::generateDropdownHtml('sort_order');

        $options = RoyalOrderHelper::getRoyalOrderOptions();
        foreach ($options as $value => $label) {
            $expectedOption = $value . ' - ' . $label;
            $this->assertStringContainsString($expectedOption, $html);
        }
    }
}