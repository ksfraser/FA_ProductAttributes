<?php

namespace Ksfraser\HTML\Composites;

use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlTd;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;

/**
 * HTML Label Row Class
 * 
 * Represents a table row with two cells: a label cell and a content cell.
 * Commonly used in forms to display field labels alongside their values.
 * 
 * Structure: <tr><td class="label" width="25%">Label:</td><td>Content</td></tr>
 * 
 * Uses proper composition with HtmlTd instead of hardcoding HTML.
 * This follows the Composite pattern - each component recursively renders itself.
 * 
 * Design Patterns:
 * - **Composite Pattern**: Composes HtmlTd cells that recursively render
 * - **Builder Pattern**: Fluent interface for setting attributes
 * 
 * SOLID Principles:
 * - Single Responsibility: Renders label/content row pairs only
 * - Open/Closed: Can be extended for custom row types
 * - Liskov Substitution: Implements HtmlElementInterface
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
 * $label = new HtmlString('Username:');
 * $content = new HtmlString('jdoe');
 * $row = new HtmlLabelRow($label, $content);
 * $row->setLabelWidth(30)->setLabelClass('form-label');
 * echo $row->getHtml();
 * // Output: <tr><td class="form-label" width="30%">Username:</td><td>jdoe</td></tr>
 * ```
 */
class HtmlLabelRow implements HtmlElementInterface
{
    /**
     * The label cell (left)
     * @var HtmlTd
     */
    protected $labelCell;

    /**
     * The content cell (right)
     * @var HtmlTd
     */
    protected $contentCell;

    /**
     * Constructor
     * 
     * @param HtmlElementInterface $label The label content (left cell)
     * @param HtmlElementInterface $content The content (right cell)
     */
    public function __construct(HtmlElementInterface $label, HtmlElementInterface $content)
    {
        // Create the label cell (left) with default class and width
        $this->labelCell = new HtmlTd($label);
        $this->labelCell->addAttribute(new HtmlAttribute('class', 'label'));
        $this->labelCell->addAttribute(new HtmlAttribute('width', '25%'));
        
        // Create the content cell (right)
        $this->contentCell = new HtmlTd($content);
    }

    /**
     * Set the width of the label cell
     * 
     * @param int $width Width as percentage (e.g., 25 for 25%)
     * @return self Fluent interface
     */
    public function setLabelWidth(int $width): self
    {
        // Update or replace the width attribute
        $this->labelCell->addAttribute(new HtmlAttribute('width', $width . '%'));
        return $this;
    }

    /**
     * Set the CSS class for the label cell
     * 
     * @param string $class The CSS class name
     * @return self Fluent interface
     */
    public function setLabelClass(string $class): self
    {
        // Update or replace the class attribute
        $this->labelCell->addAttribute(new HtmlAttribute('class', $class));
        return $this;
    }

    /**
     * Set additional attributes for the content cell
     * 
     * @param string $attributes HTML attributes (e.g., 'colspan="2" class="value"')
     * @return self Fluent interface
     * @deprecated This method parses string attributes. Better to use addContentCellAttribute()
     */
    public function setContentCellAttributes(string $attributes): self
    {
        // Parse the attributes string and add them individually
        // This is a simple implementation for backward compatibility
        if (preg_match_all('/(\w+)="([^"]*)"/', $attributes, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $this->contentCell->addAttribute(new HtmlAttribute($match[1], $match[2]));
            }
        }
        return $this;
    }

    /**
     * Add a single attribute to the content cell
     * 
     * @param string $name Attribute name
     * @param string $value Attribute value
     * @return self Fluent interface
     */
    public function addContentCellAttribute(string $name, string $value): self
    {
        $this->contentCell->addAttribute(new HtmlAttribute($name, $value));
        return $this;
    }

    /**
     * Get the HTML representation
     * 
     * Delegates to composed HtmlTd elements, which recursively call getHtml()
     * No hardcoded HTML tags - each component renders itself!
     * 
     * @return string The complete HTML table row
     */
    public function getHtml(): string
    {
        // Build row with proper nested structure - each cell renders itself recursively
        $html = '<tr>';
        $html .= $this->labelCell->getHtml();
        $html .= $this->contentCell->getHtml();
        $html .= '</tr>';
        
        return $html;
    }

    /**
     * Render the HTML to output
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
