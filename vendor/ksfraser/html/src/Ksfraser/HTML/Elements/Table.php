<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * Table - Factory/convenience wrapper for HTML table elements
 * 
 * Provides fluent interface for building tables with clean API.
 * Wraps HtmlTable with methods like addClass(), append(), etc.
 * 
 * Usage:
 * - (new Table())->addClass('my-table')->append($row1, $row2)->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class Table {
    private $element;
    private $classes = [];
    
    /**
     * Create a new table
     */
    public function __construct() {
        $this->element = new HtmlTable(new HtmlString(''));
        $this->element->setTag('table');
    }
    
    /**
     * Get the underlying HtmlTable element
     * 
     * @return HtmlTable The wrapped HTML element
     */
    public function getHtmlElement(): HtmlTable {
        return $this->element;
    }
    
    /**
     * Add a CSS class to the table
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
     * Append child elements (rows) to the table
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
     * @return string The complete HTML table element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the table to HTML string
     * 
     * @return string The complete HTML table element
     */
    public function render(): string {
        return $this->getHtml();
    }
}

