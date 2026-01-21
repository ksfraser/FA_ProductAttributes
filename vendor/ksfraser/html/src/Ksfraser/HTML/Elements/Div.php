<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * Div - Factory/convenience wrapper for HTML div element
 * 
 * Provides fluent interface for building div containers.
 * 
 * Usage:
 * - (new Div())->addClass('container')->append($elem1, $elem2)->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class Div {
    private $element;
    private $classes = [];
    
    /**
     * Create a new div element
     */
    public function __construct() {
        $this->element = new HtmlDiv(new HtmlString(''));
        $this->element->setTag('div');
    }
    
    /**
     * Get the underlying HtmlDiv element
     * 
     * @return HtmlDiv The wrapped HTML element
     */
    public function getHtmlElement(): HtmlDiv {
        return $this->element;
    }
    
    /**
     * Set the text content of the div
     * 
     * @param string $text The div text
     * @return self Fluent interface
     */
    public function setText(string $text): self {
        // Replace nested element with new text
        $this->element = new HtmlDiv(new HtmlString(htmlspecialchars($text)));
        $this->element->setTag('div');
        
        // Re-apply classes
        if (!empty($this->classes)) {
            $this->element->addAttribute(new HtmlAttribute('class', implode(' ', $this->classes)));
        }
        
        return $this;
    }
    
    /**
     * Add a CSS class to the div
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
     * Append child elements to the div
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
     * @return string The complete HTML div element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the div to HTML string
     * 
     * @return string The complete HTML div element
     */
    public function render(): string {
        return $this->getHtml();
    }
}
