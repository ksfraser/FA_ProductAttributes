<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Edit Action Button Class
 * 
 * Specialized button for edit actions in tables/forms.
 * Encapsulates the onclick handler setup and button styling for edit operations.
 * 
 * Features:
 * - Automatic onclick handler generation
 * - JavaScript function call support (e.g., editOption(...))
 * - CSS class configuration for styling
 * - Fluent interface for method chaining
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles edit button generation
 * - Open/Closed: Can be extended for custom edit behaviors
 * - Liskov Substitution: Can replace ActionButton safely
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251220
 * 
 * @example
 * ```php
 * $editBtn = new EditButton(
 *     new HtmlString('Edit'),
 *     '123',
 *     "editOption(123, 'John', 'Doe', '2025-01-01')"
 * );
 * $editBtn->setCssClass('btn btn-primary btn-sm');
 * echo $editBtn->getHtml();
 * // Output: <input type="button" value="Edit" name="edit_btn_123" onclick="editOption(123, 'John', 'Doe', '2025-01-01')" class="btn btn-primary btn-sm" />
 * ```
 */
class EditButton extends ActionButton
{
    /**
     * @var string The JavaScript function call for the onclick handler
     */
    protected $onclickFunction = '';

    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The button label (default: "Edit")
     * @param string $actionId The record/row ID to edit
     * @param string $onclickFunction Optional JavaScript function call
     */
    public function __construct(HtmlElementInterface $label, $actionId = '', $onclickFunction = '')
    {
        $this->onclickFunction = $onclickFunction;
        parent::__construct('edit', $label, $actionId);
    }

    /**
     * Setup edit button attributes
     * 
     * @return void
     */
    protected function setupActionButton()
    {
        $this->setName('edit_btn_' . $this->actionId);
        
        if (!empty($this->onclickFunction)) {
            $this->setOnclick($this->onclickFunction);
        }
        
        $this->setCssClass('btn btn-primary btn-sm');
    }

    /**
     * Set the onclick JavaScript function
     * 
     * @param string $jsFunction JavaScript function call
     * @return self Fluent interface
     */
    public function setOnclickFunction($jsFunction)
    {
        $this->onclickFunction = $jsFunction;
        $this->setOnclick($jsFunction);
        return $this;
    }
}
