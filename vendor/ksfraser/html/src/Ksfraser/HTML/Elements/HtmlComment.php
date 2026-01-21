<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlComment - HTML Comment Element
 * 
 * Represents an HTML comment <!-- comment text -->.
 * Used for annotations, documentation placeholders, or temporary notes in HTML.
 * 
 * Unlike other HTML elements, comments are not displayed in the browser
 * but are visible in the HTML source code.
 * 
 * Use Cases:
 * - Documenting integration points with FA functions
 * - Placeholder for future implementations
 * - Developer notes in generated HTML
 * - Debugging markers
 * 
 * Security Note:
 * Comments are visible in HTML source, so never include sensitive information!
 * 
 * @package Ksfraser\HTML
 * @since 20251019
 * @version 1.0.0
 * 
 * @example
 * ```php
 * // Simple comment
 * $comment = new HtmlComment('This is a placeholder');
 * echo $comment->getHtml(); // <!-- This is a placeholder -->
 * 
 * // Multi-line comment
 * $comment = new HtmlComment("Line 1\nLine 2\nLine 3");
 * echo $comment->getHtml();
 * // <!-- Line 1
 * //      Line 2
 * //      Line 3 -->
 * 
 * // Document FA integration point
 * $comment = new HtmlComment('supplier_list("partnerId_123", null)');
 * echo $comment->getHtml(); // <!-- supplier_list("partnerId_123", null) -->
 * ```
 */
class HtmlComment implements HtmlElementInterface
{
    /**
     * @var string The comment text
     */
    protected string $text;

    /**
     * Constructor
     * 
     * @param string $text The comment text (will be placed between <!-- and -->)
     */
    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * Render the HTML comment to output
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }

    /**
     * Get HTML comment as string
     * 
     * Note: Does NOT HTML-escape the content. Comments should only contain
     * trusted content (developer-written text, not user input).
     * 
     * @return string The HTML comment
     */
    public function getHtml(): string
    {
        return '<!-- ' . $this->text . ' -->';
    }

    /**
     * Get the comment text
     * 
     * @return string The raw comment text (without <!-- -->)
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Set the comment text
     * 
     * @param string $text The new comment text
     * @return self Fluent interface
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }
}
