<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * TableData - Factory/convenience wrapper for HTML table data cell element
 * 
 * Provides fluent interface for building table cells (td).
 * 
 * Usage:
 * - (new TableData())->addClass('col1')->setText('Value')->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class TableData {
    private $element;
    private $classes = [];
    
    /**
     * Create a new table data cell (td)
     */
    public function __construct() {
        $this->element = new HtmlTableRowCell(new HtmlString(''));
        $this->element->setTag('td');
    }
    
    /**
     * Get the underlying HtmlTableRowCell element
     * 
     * @return HtmlTableRowCell The wrapped HTML element
     */
    public function getHtmlElement(): HtmlTableRowCell {
        return $this->element;
    }
    
    /**
     * Set the text content of the cell
     * 
     * @param string $text The cell text
     * @return self Fluent interface
     */
    public function setText(string $text): self {
        // Replace the first nested element with new text
        $this->element = new HtmlTableRowCell(new HtmlString(htmlspecialchars($text)));
        $this->element->setTag('td');
        
        // Re-apply classes
        if (!empty($this->classes)) {
            $this->element->addAttribute(new HtmlAttribute('class', implode(' ', $this->classes)));
        }
        
        return $this;
    }
    
    /**
     * Add a CSS class to the cell
     * 
     * @param string $class CSS class name
     * @return self Fluent interface
     */
    public function addClass(string $class): self {
        if (!in_array($class, $this->classes)) {
            $this->classes[] = $class;
        }
        if (!empty($this->classes)) {
            $this->element->addAttribute(new HtmlAttribute('class', implode(' ', $this->classes)));
        }
        return $this;
    }
    
    /**
     * Append child elements to the cell
     * 
     * @param mixed ...$elements Variable number of elements to append
     * @return self Fluent interface
     */
    public function append(...$elements): self {
        foreach ($elements as $element) {
            if ($element instanceof HtmlElementInterface) {
                $this->element->addNested($element);
            } elseif (method_exists($element, 'getHtmlElement')) {
                $this->element->addNested($element->getHtmlElement());
            }
        }
        return $this;
    }
    
    /**
     * Add an HTML attribute
     * 
     * @param string $name Attribute name
     * @param string $value Attribute value
     * @return self Fluent interface
     */
    public function setAttribute(string $name, string $value): self {
        $this->element->addAttribute(new HtmlAttribute($name, $value));
        return $this;
    }
    
    /**
     * Get HTML representation as string
     * 
     * @return string The complete HTML table cell element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the cell to HTML string
     * 
     * @return string The complete HTML table cell element
     */
    public function render(): string {
        return $this->getHtml();
    }
}
