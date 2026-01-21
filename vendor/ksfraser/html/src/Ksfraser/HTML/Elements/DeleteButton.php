<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Delete Action Button Class
 * 
 * Specialized button for delete actions in tables/forms.
 * Encapsulates the deletion button styling and onclick behavior.
 * 
 * Features:
 * - Automatic confirmation dialog option
 * - Danger styling by default (red background)
 * - JavaScript function call support for custom delete handlers
 * - Form submission support for server-side deletion
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles delete button generation
 * - Open/Closed: Can be extended for custom delete behaviors
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251220
 * 
 * @example
 * ```php
 * $deleteBtn = new DeleteButton(
 *     new HtmlString('Delete'),
 *     '123'
 * );
 * $deleteBtn->setConfirmation("Are you sure you want to delete this item?");
 * echo $deleteBtn->getHtml();
 * ```
 */
class DeleteButton extends ActionButton
{
    /**
     * @var string Confirmation message for delete action
     */
    protected $confirmationMessage = 'Are you sure?';

    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The button label (default: "Delete")
     * @param string $actionId The record/row ID to delete
     */
    public function __construct(HtmlElementInterface $label, $actionId = '')
    {
        parent::__construct('delete', $label, $actionId);
    }

    /**
     * Setup delete button attributes
     * 
     * @return void
     */
    protected function setupActionButton()
    {
        $this->setName('delete_btn_' . $this->actionId);
        $this->setCssClass('btn btn-danger btn-sm');
        
        // Default confirmation onclick
        $this->setOnclick("return confirm('" . addslashes($this->confirmationMessage) . "');");
    }

    /**
     * Set custom confirmation message
     * 
     * @param string $message Confirmation message to display
     * @return self Fluent interface
     */
    public function setConfirmation($message)
    {
        $this->confirmationMessage = $message;
        $this->setOnclick("return confirm('" . addslashes($message) . "');");
        return $this;
    }

    /**
     * Disable confirmation dialog
     * 
     * @return self Fluent interface
     */
    public function noConfirmation()
    {
        $this->confirmationMessage = '';
        $this->addAttribute(new HtmlAttribute('onclick', '')); // Clear onclick
        return $this;
    }
}
