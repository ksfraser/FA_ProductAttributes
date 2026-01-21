<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Abstract Action Button Class
 * 
 * Base class for specialized action buttons (Edit, Delete, Add, Cancel).
 * Follows Single Responsibility Principle by encapsulating button-specific logic
 * for common CRUD operations.
 * 
 * Design Patterns:
 * - Template Method: Defines structure for action buttons
 * - Strategy Pattern: Subclasses implement specific action behaviors
 * - Builder Pattern: Fluent interface for chaining attributes
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles action-specific button generation
 * - Open/Closed: Open for extension via subclasses, closed for modification
 * - Liskov Substitution: Can replace HtmlInputButton safely
 * - Interface Segregation: Implements HtmlElementInterface appropriately
 * - Dependency Inversion: Depends on HtmlElementInterface abstraction
 * 
 * @package Ksfraser\HTML
 * @author Kevin Fraser
 * @since 20251220
 */
abstract class ActionButton extends HtmlInputGenericButton
{
    /**
     * @var string The action identifier (id, row id, etc.)
     */
    protected $actionId;

    /**
     * @var string The action name (edit, delete, etc.)
     */
    protected $actionName;

    /**
     * Constructor
     * 
     * @param string $actionName The name of the action (edit, delete, etc.)
     * @param HtmlElementInterface $label The button label text
     * @param string $actionId The identifier for this action (e.g., record ID)
     */
    public function __construct($actionName, HtmlElementInterface $label, $actionId = '')
    {
        parent::__construct($label);
        $this->actionName = $actionName;
        $this->actionId = $actionId;
        $this->setupActionButton();
    }

    /**
     * Setup button-specific attributes (onclick, name, class, etc.)
     * Implemented by subclasses for specific action behavior.
     * 
     * @return void
     */
    abstract protected function setupActionButton();

    /**
     * Get the action ID
     * 
     * @return string
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    /**
     * Get the action name
     * 
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Set CSS class for button styling
     * 
     * @param string $cssClass CSS class name(s)
     * @return self Fluent interface
     */
    public function setCssClass($cssClass)
    {
        return $this->setClass($cssClass);
    }

    /**
     * Add a CSS class to existing classes
     * 
     * @param string $cssClass CSS class to add
     * @return self Fluent interface
     */
    public function addCssClass($cssClass)
    {
        return $this->setClass($cssClass);
    }
}
