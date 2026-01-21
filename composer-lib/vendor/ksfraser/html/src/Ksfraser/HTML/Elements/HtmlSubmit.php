<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlInputButton;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HTML Submit Button Class
 * 
 * Represents an HTML <input type="submit"> button element.
 * Used for form submissions.
 * 
 * Extends HtmlInputButton to inherit common button-type input behavior.
 * 
 * Design Pattern: Builder Pattern
 * - Fluent interface for setting attributes
 * 
 * SOLID Principles:
 * - Single Responsibility: Renders submit button only
 * - Open/Closed: Can be extended for custom submit buttons
 * - Liskov Substitution: Can replace HtmlInputButton
 * - Interface Segregation: Uses HtmlElementInterface appropriately
 * - Dependency Inversion: Depends on HtmlElementInterface abstraction
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251019
 * @version 20251019.1
 * 
 * @example
 * ```php
 * $label = new HtmlString('Save');
 * $submit = new HtmlSubmit($label);
 * $submit->setName('save_btn')->setClass('btn btn-primary');
 * echo $submit->getHtml(); // <input type="submit" value="Save" name="save_btn" class="btn btn-primary" />
 * ```
 */
class HtmlSubmit extends HtmlInputButton
{
    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The button label (will be value attribute)
     */
    public function __construct(HtmlElementInterface $label)
    {
        // Call parent with "submit" type
        parent::__construct("submit", $label);
    }
}
