<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Cancel Action Button Class
 * 
 * Specialized button for cancel actions.
 * Encapsulates cancel button styling and behavior.
 * 
 * Features:
 * - Secondary styling by default (gray background)
 * - JavaScript function call support for custom cancel handlers
 * - Optional redirect functionality
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles cancel button generation
 * - Open/Closed: Can be extended for custom cancel behaviors
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251220
 * 
 * @example
 * ```php
 * $cancelBtn = new CancelButton(new HtmlString('Cancel'));
 * $cancelBtn->setOnclickFunction("window.history.back();");
 * echo $cancelBtn->getHtml();
 * ```
 */
class CancelButton extends ActionButton
{
    /**
     * Constructor
     * 
     * @param HtmlElementInterface|null $label The button label (default: "Cancel")
     */
    public function __construct(HtmlElementInterface $label = null)
    {
        if ($label === null) {
            $label = new HtmlString('Cancel');
        }
        parent::__construct('cancel', $label);
    }

    /**
     * Setup cancel button attributes
     * 
     * @return void
     */
    protected function setupActionButton()
    {
        $this->setName('cancel_btn');
        $this->setCssClass('btn btn-secondary btn-sm');
    }

    /**
     * Set onclick JavaScript function
     * 
     * @param string $jsFunction JavaScript function call
     * @return self Fluent interface
     */
    public function setOnclickFunction($jsFunction)
    {
        $this->setOnclick($jsFunction);
        return $this;
    }

    /**
     * Set cancel to go back in history
     * 
     * @return self Fluent interface
     */
    public function setGoBack()
    {
        return $this->setOnclickFunction("window.history.back();");
    }
}
