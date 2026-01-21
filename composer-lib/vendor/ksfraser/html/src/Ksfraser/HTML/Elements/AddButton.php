<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Add Action Button Class
 * 
 * Specialized button for add/create actions.
 * Encapsulates the add button styling and behavior.
 * 
 * Features:
 * - Success styling by default (green background)
 * - Form submission support
 * - JavaScript function call support for custom add handlers
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles add button generation
 * - Open/Closed: Can be extended for custom add behaviors
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251220
 * 
 * @example
 * ```php
 * $addBtn = new AddButton(new HtmlString('Add New'));
 * $addBtn->setCssClass('btn btn-success btn-lg');
 * echo $addBtn->getHtml();
 * ```
 */
class AddButton extends ActionButton
{
    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The button label (default: "Add")
     */
    public function __construct(HtmlElementInterface $label)
    {
        parent::__construct('add', $label);
    }

    /**
     * Setup add button attributes
     * 
     * @return void
     */
    protected function setupActionButton()
    {
        $this->setName('add_btn');
        $this->setCssClass('btn btn-success btn-sm');
    }
}
