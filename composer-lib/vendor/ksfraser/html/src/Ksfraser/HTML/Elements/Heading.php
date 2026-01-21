<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Heading - Factory class for HTML heading elements
 * 
 * Provides a convenient, fluent interface for creating heading elements (H1-H6).
 * Wraps HtmlHeading1-6 classes with a clean API.
 * 
 * Usage:
 * - Basic: (new Heading(3))->setText('Title')->render()
 * - Fluent: (new Heading(2))->addClass('title')->setText('Welcome')->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class Heading {
    private $element;
    private $level;
    private $textContent = '';
    private $classes = [];
    
    /**
     * Create a heading element at the specified level
     * 
     * @param int $level Heading level (1-6, default: 2)
     */
    public function __construct(int $level = 2) {
        $this->level = max(1, min(6, $level));
        $className = 'Ksfraser\HTML\Elements\HtmlHeading' . $this->level;
        // Create with empty string initially
        $this->element = new $className(new HtmlString(''));
    }
    
    /**
     * Get the underlying HtmlHeading element
     * 
     * @return HtmlHeading The wrapped HTML element
     */
    public function getHtmlElement(): HtmlHeading {
        return $this->element;
    }
    
    /**
     * Set the text content of the heading
     * 
     * @param string $text The heading text
     * @return self Fluent interface
     */
    public function setText(string $text): self {
        $this->textContent = $text;
        // Rebuild the element with new text
        $className = 'Ksfraser\HTML\Elements\HtmlHeading' . $this->level;
        $this->element = new $className(new HtmlString(htmlspecialchars($text)));
        return $this;
    }
    
    /**
     * Add a CSS class to the heading
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
     * Get HTML representation as string
     * 
     * @return string HTML representation
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the heading to HTML string
     * 
     * @return string HTML representation
     */
    public function render(): string {
        return $this->getHtml();
    }
}
