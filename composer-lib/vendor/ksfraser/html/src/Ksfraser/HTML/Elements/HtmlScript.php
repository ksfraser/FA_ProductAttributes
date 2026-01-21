<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

/**
 * HtmlScript - Script Tag Container
 * 
 * Represents an HTML <script> element for including JavaScript code.
 * Can contain inline JavaScript or reference external scripts.
 * 
 * Design Pattern: Builder Pattern
 * - Fluent interface for setting attributes and content
 * 
 * SOLID Principles:
 * - Single Responsibility: Renders script tags only
 * - Open/Closed: Can be extended for specialized script types
 * - Liskov Substitution: Can replace HtmlElement
 * - Interface Segregation: Uses HtmlElement appropriately
 * - Dependency Inversion: Depends on HtmlElement abstraction
 * 
 * Security Note:
 * - Inline scripts are vulnerable to XSS attacks
 * - Always sanitize content from user input
 * - Consider Content Security Policy (CSP) for script restrictions
 * 
 * @package    Ksfraser\HTML
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251220
 * @version    1.0.0
 * 
 * @example
 * ```php
 * // Inline script
 * $script = (new HtmlScript())
 *     ->setContent("console.log('Hello, World!');")
 *     ->addAttribute('type', 'text/javascript');
 * echo $script->getHtml();
 * // Output: <script type="text/javascript">console.log('Hello, World!');</script>
 * 
 * // External script reference
 * $script = (new HtmlScript())
 *     ->setAttribute('src', '/path/to/script.js')
 *     ->setAttribute('async', 'async');
 * echo $script->getHtml();
 * // Output: <script src="/path/to/script.js" async></script>
 * 
 * // With type parameter
 * $script = (new HtmlScript('text/javascript', 'alert("Hi");'));
 * echo $script->getHtml();
 * // Output: <script type="text/javascript">alert("Hi");</script>
 * ```
 */
class HtmlScript extends HtmlElement
{
    /**
     * Script content (inline JavaScript)
     * 
     * @var string|null
     */
    protected $content = null;

    /**
     * Constructor
     * 
     * @param string|null $type    Optional script type (defaults to 'text/javascript')
     * @param string|null $content Optional inline script content
     */
    public function __construct($type = null, $content = null)
    {
        parent::__construct();
        
        $this->tagName = 'script';
        
        if ($type) {
            $this->setAttribute('type', $type);
        } else {
            $this->setAttribute('type', 'text/javascript');
        }
        
        if ($content) {
            $this->content = $content;
        }
    }

    /**
     * Set inline script content
     * 
     * @param string $content The JavaScript code to include
     * @return HtmlScript Fluent interface
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get inline script content
     * 
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Generate the HTML representation
     * 
     * @return string
     */
    public function getHtml()
    {
        $html = '<' . $this->tagName;
        
        // Add all attributes
        foreach ($this->attributes as $key => $value) {
            if ($value === null || $value === '') {
                $html .= ' ' . $key;
            } else {
                $html .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        
        $html .= '>';
        
        // Add inline content if present
        if ($this->content !== null) {
            $html .= "\n" . $this->content . "\n";
        }
        
        $html .= '</' . $this->tagName . '>';
        
        return $html;
    }
}
