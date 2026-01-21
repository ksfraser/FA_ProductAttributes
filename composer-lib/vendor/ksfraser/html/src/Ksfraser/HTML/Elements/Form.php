<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;

/**
 * Form - Factory/convenience wrapper for HTML form element
 * 
 * Provides fluent interface for building forms.
 * 
 * Usage:
 * - (new Form())->setMethod('post')->setAction('/submit')->append($field1, $field2)->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class Form {
    private $element;
    private $classes = [];
    
    /**
     * Create a new form element
     */
    public function __construct() {
        $this->element = new HtmlForm(new HtmlString(''));
        $this->element->setTag('form');
    }
    
    /**
     * Get the underlying HtmlForm element
     * 
     * @return HtmlForm The wrapped HTML element
     */
    public function getHtmlElement(): HtmlForm {
        return $this->element;
    }
    
    /**
     * Set the form method attribute (GET or POST)
     * 
     * @param string $method HTTP method (get, post)
     * @return self Fluent interface
     */
    public function setMethod(string $method): self {
        $this->element->addAttribute(new HtmlAttribute('method', strtolower($method)));
        return $this;
    }
    
    /**
     * Set the form action attribute
     * 
     * @param string $action Form action URL
     * @return self Fluent interface
     */
    public function setAction(string $action): self {
        $this->element->addAttribute(new HtmlAttribute('action', htmlspecialchars($action)));
        return $this;
    }
    
    /**
     * Set the form id attribute
     * 
     * @param string $id Form id
     * @return self Fluent interface
     */
    public function setId(string $id): self {
        $this->element->addAttribute(new HtmlAttribute('id', $id));
        return $this;
    }
    
    /**
     * Add a CSS class to the form
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
     * Append child elements (form fields) to the form
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
     * @return string The complete HTML form element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the form to HTML string
     * 
     * @return string The complete HTML form element
     */
    public function render(): string {
        return $this->getHtml();
    }
}
