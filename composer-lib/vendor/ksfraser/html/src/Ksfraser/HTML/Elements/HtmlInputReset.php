<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlInputButton;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HTML Reset Button Class
 * 
 * Represents an HTML <input type="reset"> button element.
 * Used to reset form fields to their default values.
 * 
 * Extends HtmlInputButton to inherit common button-type input behavior.
 * 
 * Design Pattern: Builder Pattern
 * - Fluent interface for setting attributes
 * 
 * SOLID Principles:
 * - Single Responsibility: Renders reset button only
 * - Open/Closed: Can be extended for custom reset buttons
 * - Liskov Substitution: Can replace HtmlInputButton
 * - Interface Segregation: Uses HtmlElementInterface appropriately
 * - Dependency Inversion: Depends on HtmlElementInterface abstraction
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251019
 * @version 20251019.0
 * 
 * @example
 * ```php
 * $label = new HtmlString('Reset Form');
 * $reset = new HtmlInputReset($label);
 * $reset->setName('reset_btn')->setClass('btn btn-secondary');
 * echo $reset->getHtml(); // <input type="reset" value="Reset Form" name="reset_btn" class="btn btn-secondary" />
 * ```
 */
class HtmlInputReset extends HtmlInputButton
{
    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The button label (will be value attribute)
     */
    public function __construct(HtmlElementInterface $label)
    {
        // Call parent with "reset" type
        parent::__construct("reset", $label);
    }
}
