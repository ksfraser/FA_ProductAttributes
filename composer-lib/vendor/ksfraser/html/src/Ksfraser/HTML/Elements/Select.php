<?php

namespace Ksfraser\HTML\Elements;

/**
 * Select - Convenience Alias for HtmlSelect
 * 
 * Shorter, cleaner import name for the HtmlSelect class.
 * Allows using `new Select()` instead of `new HtmlSelect()`.
 * 
 * This is a simple class alias that extends HtmlSelect, providing
 * the same functionality with more concise naming for common usage patterns.
 * 
 * @package    Ksfraser\HTML
 * @author     Kevin Fraser
 * @since      20251220
 * @version    1.0.0
 * 
 * @example
 * ```php
 * use Ksfraser\HTML\Elements\Select;
 * use Ksfraser\HTML\Elements\Option;
 * 
 * // Using the shorter alias
 * $select = (new Select('country'))->addOptionsFromArray([
 *     'ca' => 'Canada',
 *     'us' => 'United States',
 *     'mx' => 'Mexico'
 * ]);
 * ```
 */
class Select extends HtmlSelect
{
    // Inherits all functionality from HtmlSelect
    // Provides shorter, cleaner naming
}
