<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlInputButton;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;

/**
 * HTML Generic Button Input Class
 * 
 * Represents an HTML <input type="button"> element.
 * Used for client-side JavaScript interactions without form submission.
 * Unlike submit/reset buttons, this button has no default behavior.
 * 
 * Extends HtmlInputButton to inherit common button-type input behavior.
 * 
 * Design Pattern: Builder Pattern
 * - Fluent interface for setting attributes
 * 
 * SOLID Principles:
 * - Single Responsibility: Renders generic button only
 * - Open/Closed: Can be extended for custom button types
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
 * $label = new HtmlString('Click Me');
 * $button = new HtmlInputGenericButton($label);
 * $button->setName('my_btn')
 *        ->setOnclick("alert('Hello!')")
 *        ->setClass('btn btn-primary');
 * echo $button->getHtml(); 
 * // Output: <input type="button" value="Click Me" name="my_btn" onclick="alert('Hello!')" class="btn btn-primary" />
 * ```
 */
class HtmlInputGenericButton extends HtmlInputButton
{
    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The button label (will be value attribute)
     */
    public function __construct(HtmlElementInterface $label)
    {
        // Call parent with "button" type
        parent::__construct("button", $label);
    }

    /**
     * Set the onclick JavaScript event handler
     * 
     * @param string $javascript The JavaScript code to execute on click
     * @return self Fluent interface
     */
    public function setOnclick(string $javascript): self
    {
        $this->addAttribute(new HtmlAttribute("onclick", $javascript));
        return $this;
    }
}
