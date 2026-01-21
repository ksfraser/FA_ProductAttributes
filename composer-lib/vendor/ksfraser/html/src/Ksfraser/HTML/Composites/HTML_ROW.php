<?php

namespace Ksfraser\HTML\Composites;

use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HTML_ROW - Wrapper for HtmlTableRow
 * 
 * Provides backward compatibility with legacy code.
 * This is a simple wrapper that delegates to HtmlTableRow.
 * 
 * @deprecated This class exists for backward compatibility only.
 *             New code should use HtmlTableRow directly.
 * @see \Ksfraser\HTML\HtmlTableRow
 * 
 * @package Ksfraser\HTML
 * @since 20251019 - Converted to wrapper
 */
class HTML_ROW implements HtmlElementInterface
{
	/**
	 * The underlying HtmlTableRow instance
	 * @var HtmlTableRow
	 */
	protected $row;
	
	/**
	 * Constructor
	 * 
	 * @param string|HtmlElementInterface $data The row content
	 */
	function __construct( $data )
	{
		$content = is_string($data) ? new HtmlString($data) : $data;
		$this->row = new HtmlTableRow( $content );
	}
	
	/**
	 * Render the row to HTML output
	 * 
	 * @return void
	 */
	function toHTML(): void
	{
		$this->row->toHtml();
	}
	
	/**
	 * Get the HTML string representation
	 * 
	 * @return string The HTML
	 */
	public function getHtml(): string
	{
		return $this->row->getHtml();
	}
}
