<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlEmptyElement;

/**
 * Input - Factory/convenience wrapper for HTML input element
 * 
 * Provides fluent interface for building form input fields.
 * 
 * Usage:
 * - (new Input())->setType('text')->setName('username')->setAttribute('placeholder', 'Enter username')->render()
 * 
 * @package Ksfraser\HTML\Elements
 */
class Input {
    private $element;
    
    /**
     * Create a new input element
     * 
     * @param string $type Optional input type (text, email, password, hidden, etc.)
     *                      If provided, automatically sets the type attribute
     */
    public function __construct(string $type = '') {
        // Create a basic HtmlEmptyElement for input instead of HtmlInput
        // to avoid HtmlInput's automatic type="text" addition
        $this->element = new HtmlEmptyElement();
        $this->element->setTag('input');
        
        // Only add type if explicitly provided
        if ($type) {
            $this->element->addAttribute(new HtmlAttribute('type', $type));
        }
    }
    
    /**
     * Get the underlying HTML element
     * 
     * @return HtmlEmptyElement The wrapped HTML element
     */
    public function getHtmlElement(): HtmlEmptyElement {
        return $this->element;
    }
    
    /**
     * Set the input type attribute
     * 
     * @param string $type Input type (text, email, password, hidden, checkbox, radio, etc.)
     * @return self Fluent interface
     */
    public function setType(string $type): self {
        $this->element->addAttribute(new HtmlAttribute('type', $type));
        return $this;
    }
    
    /**
     * Set the input name attribute
     * 
     * @param string $name Input name
     * @return self Fluent interface
     */
    public function setName(string $name): self {
        $this->element->addAttribute(new HtmlAttribute('name', $name));
        return $this;
    }
    
    /**
     * Set the input value attribute
     * 
     * @param string $value Input value
     * @return self Fluent interface
     */
    public function setValue(string $value): self {
        $this->element->addAttribute(new HtmlAttribute('value', htmlspecialchars($value)));
        return $this;
    }
    
    /**
     * Set the input id attribute
     * 
     * @param string $id Input id
     * @return self Fluent interface
     */
    public function setId(string $id): self {
        $this->element->addAttribute(new HtmlAttribute('id', $id));
        return $this;
    }
    
    /**
     * Set the placeholder attribute
     * 
     * @param string $placeholder Placeholder text
     * @return self Fluent interface
     */
    public function setPlaceholder(string $placeholder): self {
        $this->element->addAttribute(new HtmlAttribute('placeholder', htmlspecialchars($placeholder)));
        return $this;
    }
    
    /**
     * Mark the input as required
     * 
     * @param bool $required Whether the input is required
     * @return self Fluent interface
     */
    public function setRequired(bool $required = true): self {
        if ($required) {
            $this->element->addAttribute(new HtmlAttribute('required', 'required'));
        }
        return $this;
    }
    
    /**
     * Set the input step attribute (for number inputs)
     * 
     * @param string $step Step value
     * @return self Fluent interface
     */
    public function setStep(string $step): self {
        $this->element->addAttribute(new HtmlAttribute('step', $step));
        return $this;
    }
    
    /**
     * Set the input min attribute
     * 
     * @param string $min Minimum value
     * @return self Fluent interface
     */
    public function setMin(string $min): self {
        $this->element->addAttribute(new HtmlAttribute('min', $min));
        return $this;
    }
    
    /**
     * Set the input max attribute
     * 
     * @param string $max Maximum value
     * @return self Fluent interface
     */
    public function setMax(string $max): self {
        $this->element->addAttribute(new HtmlAttribute('max', $max));
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
     * @return string The complete HTML input element
     */
    public function getHtml(): string {
        return $this->element->getHtml();
    }
    
    /**
     * Render the input to HTML string
     * 
     * @return string The complete HTML input element
     */
    public function render(): string {
        return $this->getHtml();
    }
}

