<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * Button - Factory/convenience wrapper for HTML button element
 * 
 * Provides fluent interface for building buttons.
 * 
 * Usage:
 * - (new Button())->setText('Click Me')->addClass('btn-primary')->setType('submit')->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class Button {
    private $element;
    private $classes = [];
    
    /**
     * Create a new button element
     */
    public function __construct() {
        $this->element = new HtmlButton(new HtmlString(''));
        $this->element->setTag('button');
    }
    
    /**
     * Get the underlying HtmlButton element
     * 
     * @return HtmlButton The wrapped HTML element
     */
    public function getHtmlElement(): HtmlButton {
        return $this->element;
    }
    
    /**
     * Set the text content of the button
     * 
     * @param string $text The button text
     * @return self Fluent interface
     */
    public function setText(string $text): self {
        // Replace nested element with new text
        $this->element = new HtmlButton(new HtmlString(htmlspecialchars($text)));
        $this->element->setTag('button');
        
        // Re-apply classes
        if (!empty($this->classes)) {
            $this->element->addAttribute(new HtmlAttribute('class', implode(' ', $this->classes)));
        }
        
        return $this;
    }
    
    /**
     * Add a CSS class to the button
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
     * Set the button type attribute
     * 
     * @param string $type Button type (button, submit, reset, etc.)
     * @return self Fluent interface
     */
    public function setType(string $type): self {
        $this->element->addAttribute(new HtmlAttribute('type', $type));
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
     * @return string The complete HTML button element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the button to HTML string
     * 
     * @return string The complete HTML button element
     */
    public function render(): string {
        return $this->getHtml();
    }
}
