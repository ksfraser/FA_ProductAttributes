<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * TableHeader - Factory/convenience wrapper for HTML table header cell element
 * 
 * Provides fluent interface for building table header cells (th).
 * 
 * Usage:
 * - (new TableHeader())->setText('Column Name')->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class TableHeader {
    private $element;
    private $classes = [];
    
    /**
     * Create a new table header cell (th)
     */
    public function __construct() {
        $this->element = new HtmlTableHeaderCell(new HtmlString(''));
        $this->element->setTag('th');
    }
    
    /**
     * Get the underlying HtmlTableHeaderCell element
     * 
     * @return HtmlTableHeaderCell The wrapped HTML element
     */
    public function getHtmlElement(): HtmlTableHeaderCell {
        return $this->element;
    }
    
    /**
     * Set the text content of the header cell
     * 
     * @param string $text The header text
     * @return self Fluent interface
     */
    public function setText(string $text): self {
        // Replace the first nested element with new text
        $this->element = new HtmlTableHeaderCell(new HtmlString(htmlspecialchars($text)));
        $this->element->setTag('th');
        
        // Re-apply classes
        if (!empty($this->classes)) {
            $this->element->addAttribute(new HtmlAttribute('class', implode(' ', $this->classes)));
        }
        
        return $this;
    }
    
    /**
     * Add a CSS class to the header cell
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
     * Append child elements to the header cell
     * 
     * @param mixed ...$elements Variable number of elements to append
     * @return self Fluent interface
     */
    public function append(...$elements): self {
        foreach ($elements as $element) {
            if ($element instanceof HtmlElementInterface) {
                $this->element->addNested($element);
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
     * @return string The complete HTML table header cell element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the header cell to HTML string
     * 
     * @return string The complete HTML table header cell element
     */
    public function render(): string {
        return $this->getHtml();
    }
}
