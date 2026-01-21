<?php
namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlEmptyElement;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Stylesheet - HTML link element for CSS stylesheets
 * 
 * Extends HtmlEmptyElement to generate properly formatted <link rel="stylesheet"> tags.
 * Provides fluent interface for setting href and rel attributes with security encoding.
 * 
 * Usage:
 * ```php
 * $stylesheet = (new Stylesheet())
 *     ->setHref('https://example.com/styles.css')
 *     ->setRel('stylesheet');
 * echo $stylesheet->getHtml();
 * // Output: <link rel="stylesheet" href="https://example.com/styles.css" />
 * ```
 * 
 * @package Ksfraser\HTML\Elements
 */
class Stylesheet extends HtmlEmptyElement
{
    /**
     * Constructor - Initialize as self-closing link element
     */
    public function __construct()
    {
        parent::__construct();
        $this->tag = 'link';
    }

    /**
     * Set href attribute with HTML security encoding
     * 
     * Automatically applies security encoding to prevent XSS attacks
     * and ensure valid HTML attribute syntax.
     * 
     * @param string $url The URL to the stylesheet file
     * @return self Fluent interface for chaining
     */
    public function setHref(string $url): self
    {
        // Encode URL for HTML attribute context
        $encodedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $this->addAttribute(new HtmlAttribute('href', $encodedUrl));
        return $this;
    }

    /**
     * Set rel attribute (should always be "stylesheet" for CSS)
     * 
     * @param string $rel The rel value (default: "stylesheet")
     * @return self Fluent interface for chaining
     */
    public function setRel(string $rel = 'stylesheet'): self
    {
        $this->addAttribute(new HtmlAttribute('rel', $rel));
        return $this;
    }
}
