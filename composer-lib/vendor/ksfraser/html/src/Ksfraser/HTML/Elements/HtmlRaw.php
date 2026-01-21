<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlRaw - Raw HTML content without escaping
 * 
 * This class is for passing pre-sanitized HTML content that should
 * NOT be HTML-escaped. Use with caution - only for trusted HTML!
 * 
 * Unlike HtmlString (which HTML-escapes for safety), HtmlRaw passes
 * through the HTML as-is. This is useful for:
 * - Pre-generated HTML from other components
 * - HTML markup that needs to be preserved (e.g., <b>, <a>, etc.)
 * - Content from trusted sources
 * 
 * Security Warning:
 * Never use HtmlRaw with user input! Always sanitize first or use HtmlString.
 * 
 * @package Ksfraser\HTML
 * @since 20251019
 * @version 1.0.0
 * 
 * @example
 * ```php
 * // Safe - content from other HTML components
 * $content = new HtmlRaw('<b>Important</b> text');
 * $row = new HtmlLabelRow(new HtmlString('Label:'), $content);
 * 
 * // UNSAFE - never do this with user input!
 * $userInput = $_POST['comment']; // ❌ DANGEROUS
 * $content = new HtmlRaw($userInput); // ❌ XSS vulnerability!
 * 
 * // Safe - user input escaped
 * $content = new HtmlString($_POST['comment']); // ✅ Escaped
 * ```
 */
class HtmlRaw implements HtmlElementInterface
{
    /**
     * @var string The raw HTML content
     */
    protected string $html;

    /**
     * Constructor
     * 
     * @param string $html Raw HTML content (will NOT be escaped)
     */
    public function __construct(string $html)
    {
        $this->html = $html;
    }

    /**
     * Render the HTML to output
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
     * Returns the raw HTML without any escaping.
     * 
     * @return string Raw HTML (unescaped)
     */
    public function getHtml(): string
    {
        return $this->html;
    }
}
