<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttributeList;
use Ksfraser\HTML\HtmlAttribute;

require_once( 'HtmlAttributeList.php' );

/**
 * HTML Element Class
 * 
 * An HTML element is defined by a start tag, some content, and an end tag.
 * Elements can have nested elements and attributes.
 * 
 * Follows Single Responsibility Principle: Renders HTML elements
 * 
 * @link https://www.w3schools.com/html/html_elements.asp
 * @author Kevin Fraser
 * @since 20250119
 * @version 20250119
 */
class HtmlElement implements HtmlElementInterface {
    /** @var string HTML tag name */
    protected $tag;
    
    /** @var HtmlElementInterface[] Array of nested child elements */
    protected $nested;
    
    /** @var bool Whether this is an empty element (no closing tag) */
    protected $empty;
    
    /** @var HtmlAttributeList List of HTML attributes */
    protected $attributeList;
    
    /**
     * Constructor
     * 
     * @param HtmlElementInterface $data Initial nested element
     */
    function __construct(HtmlElementInterface $data)
    {
        $this->nested = array();
        $this->addNested($data);
        $this->newAttributeList();
        $this->empty = false;
    }

    /**
     * Initialize a new attribute list
     * 
     * @return void
     */
    function newAttributeList(): void
    {
        $this->attributeList = new HtmlAttributeList(new HtmlAttribute("", ""));
    }
    
    /**
     * Add a nested child element
     * 
     * @param HtmlElementInterface $element Element to nest
     * @return void
     */
    function addNested(HtmlElementInterface $element): void
    {
        $this->nested[] = $element;
    }
    
    /**
     * Add an HTML attribute
     * 
     * @param HtmlAttribute $attribute Attribute to add
     * @return void
     */
    function addAttribute(HtmlAttribute $attribute): void
    {
        $this->attributeList->addAttribute($attribute);
    }
    
    /**
     * Set the entire attribute list
     * 
     * @param HtmlAttributeList $list New attribute list
     * @return void
     */
    function setAttributeList(HtmlAttributeList $list): void
    {
        $this->attributeList = $list;
    }
    
    /**
     * Set the HTML tag name
     * 
     * @param string $tag Tag name (lowercase for XHTML compliance)
     * @return void
     */
    function setTag(string $tag): void
    {
        $this->tag = strtolower($tag); // XHTML requires lowercase
    }

    /**
     * Render children elements to HTML
     * 
     * @return string HTML string of all nested children
     */
    protected function renderChildrenHtml(): string
    {
        $html = '';
        foreach ($this->nested as $child) {
            $html .= $child->getHtml();
        }
        return $html;
    }

    /**
     * Renders the object in HTML.
     * The HTML is echoed directly into the output.
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
    
    /**
     * Get HTML representation as string
     * 
     * @return string Complete HTML element string
     */
    public function getHtml(): string
    {
        $html = '<' . $this->tag;
        $html .= $this->getAttributes();
        $html .= '>';
        
        if (!$this->empty) {
            $html .= $this->renderChildrenHtml();
            $html .= '</' . $this->tag . '>';
        }
        
        return $html;
    }
    
    /**
     * Get HTML attributes as string
     * 
     * @return string Formatted attribute string
     */
    protected function getAttributes(): string
    {
        $html = " ";
        $html .= $this->attributeList->getHtml() . " ";
        return $html;
    }
}
