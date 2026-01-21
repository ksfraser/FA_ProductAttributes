<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlInput;

/**
 * HtmlHidden - Hidden Input Field
 * 
 * Convenience class for creating hidden form fields.
 * Extends HtmlInput with type="hidden" pre-configured.
 * 
 * Hidden fields are used to pass data in forms without displaying it to the user.
 * Common uses:
 * - CSRF tokens
 * - Record IDs
 * - State information
 * - Form metadata
 * 
 * Security Note:
 * Hidden fields are visible in HTML source and can be modified by users.
 * Never rely on hidden fields for security - always validate on server side.
 * 
 * @package    Ksfraser\HTML
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251023
 * @version    1.0.0
 * 
 * @example
 * ```php
 * // Basic hidden field
 * $hidden = new HtmlHidden("user_id", "12345");
 * echo $hidden->getHtml();
 * // Output: <input type="hidden" name="user_id" value="12345">
 * 
 * // With fluent interface
 * $hidden = (new HtmlHidden())
 *     ->setName("customer_id")
 *     ->setValue("42");
 * echo $hidden->getHtml();
 * // Output: <input type="hidden" name="customer_id" value="42">
 * ```
 */
class HtmlHidden extends HtmlInput
{
    /**
     * Constructor
     * 
     * @param string|null $name  Optional field name
     * @param string|null $value Optional field value
     */
    public function __construct(?string $name = null, ?string $value = null)
    {
        // Call parent with "hidden" type
        parent::__construct("hidden");
        
        // Set name if provided
        if ($name !== null) {
            $this->setName($name);
        }
        
        // Set value if provided
        if ($value !== null) {
            $this->setValue($value);
        }
    }
}
