<?php

namespace Ksfraser\FA_ProductAttributes\UI;

/**
 * Royal Order of Adjectives utility class
 *
 * Provides dropdown options and utilities for the Royal Order of Adjectives
 * as defined in linguistic best practices for English adjective sequencing.
 *
 * Follows Single Responsibility Principle by encapsulating all Royal Order logic.
 *
 * @package Ksfraser\FA_ProductAttributes\UI
 */
class RoyalOrderHelper
{
    /**
     * Get the complete Royal Order options as an associative array
     *
     * @return array<int, string> Array with sort order as key and label as value
     */
    public static function getRoyalOrderOptions(): array
    {
        return [
            1 => _("Quantity"),
            2 => _("Opinion"),
            3 => _("Size"),
            4 => _("Age"),
            5 => _("Shape"),
            6 => _("Color"),
            7 => _("Proper adjective"),
            8 => _("Material"),
            9 => _("Purpose")
        ];
    }

    /**
     * Get a specific Royal Order label by sort order number
     *
     * @param int $sortOrder The sort order number (1-9)
     * @return string|null The label for the sort order, or null if invalid
     */
    public static function getRoyalOrderLabel(int $sortOrder): ?string
    {
        $options = self::getRoyalOrderOptions();
        return $options[$sortOrder] ?? null;
    }

    /**
     * Generate HTML for Royal Order dropdown
     *
     * @param string $name The name attribute for the select element
     * @param int $selectedValue The currently selected value
     * @param array $attributes Additional HTML attributes for the select element
     * @return string HTML string for the dropdown
     */
    public static function generateDropdownHtml(string $name, int $selectedValue = 0, array $attributes = []): string
    {
        $html = '<select name="' . htmlspecialchars($name) . '"';

        foreach ($attributes as $attr => $value) {
            $html .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
        }

        $html .= '>';

        foreach (self::getRoyalOrderOptions() as $value => $label) {
            $selected = ($value === $selectedValue) ? ' selected' : '';
            $html .= '<option value="' . $value . '"' . $selected . '>' . $value . ' - ' . htmlspecialchars($label) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Validate if a sort order is within the valid Royal Order range
     *
     * @param int $sortOrder The sort order to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidSortOrder(int $sortOrder): bool
    {
        return $sortOrder >= 1 && $sortOrder <= 9;
    }

    /**
     * Get the minimum valid sort order
     *
     * @return int
     */
    public static function getMinSortOrder(): int
    {
        return 1;
    }

    /**
     * Get the maximum valid sort order
     *
     * @return int
     */
    public static function getMaxSortOrder(): int
    {
        return 9;
    }
}