<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * TableRow - Factory/convenience wrapper for HTML table row elements
 * 
 * Provides fluent interface for building table rows.
 * 
 * Usage:
 * - (new TableRow())->addClass('header')->append($td1, $td2)->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class TableRow {
    private $element;
    private $classes = [];
    
    /**
     * Create a new table row
     */
    public function __construct() {
        $this->element = new HtmlTableRow(new HtmlString(''));
        $this->element->setTag('tr');
    }
    
    /**
     * Get the underlying HtmlTableRow element
     * 
     * @return HtmlTableRow The wrapped HTML element
     */
    public function getHtmlElement(): HtmlTableRow {
        return $this->element;
    }
    
    /**
     * Add a CSS class to the row
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
     * Add headers from an array of labels
     * 
     * Creates TableHeader elements from an array of label strings.
     * Useful for building header rows with a simple array of column names.
     * 
     * @param array $labels Array of header label strings
     * @return self Fluent interface
     */
    public function addHeadersFromArray(array $labels): self {
        foreach ($labels as $label) {
            $this->append(new TableHeader($label));
        }
        return $this;
    }
    
    /**
     * Append child elements (cells) to the row
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
     * @return string The complete HTML table row element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the row to HTML string
     * 
     * @return string The complete HTML table row element
     */
    public function render(): string {
        return $this->getHtml();
    }
}
