<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * HTML Empty Element
 * 
 * Empty elements have no content and no closing tag.
 * Examples: <input>, <br>, <hr>, <img>
 * 
 * @package Ksfraser\HTML
 * @since 20251019
 */
class HtmlEmptyElement extends HtmlElement
{
	/**
	 * Constructor for empty elements
	 * 
	 * Empty elements don't have children, so we pass an empty HtmlString
	 * 
	 * @param HtmlElementInterface|null $data Optional data (ignored for empty elements)
	 */
	function __construct( $data = null )
	{
		// Empty elements don't have children - pass empty HtmlString
		parent::__construct( new HtmlString("") );
		$this->empty = true;
	}
}
