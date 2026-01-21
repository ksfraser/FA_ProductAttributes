<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * HtmlRawString - Unescaped HTML string wrapper
 * 
 * Like HtmlString but does NOT escape HTML entities.
 * Use this when you have pre-generated HTML from View classes.
 * 
 * WARNING: Only use with trusted HTML to prevent XSS vulnerabilities.
 * 
 * @package HTML
 * @since 20251219
 */
class HtmlRawString implements HtmlElementInterface
{
	protected $string;

	public function __construct($string)
	{
		$this->string = $string;
	}

	/**
	 * Renders the object in HTML.
	 * The Html is echoed directly into the output.
	 */
	public function toHtml(): void
	{
		echo $this->getHtml();
	}

	/**
	 * Get the HTML string without escaping
	 * 
	 * @return string The raw HTML string
	 */
	public function getHtml(): string
	{
		// Return the string as-is, without escaping
		// This is for pre-generated HTML from View classes
		return $this->string;
	}
}
