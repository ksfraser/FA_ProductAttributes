<?php

namespace Ksfraser\HTML\Composites;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;
use Exception;

/**
 * LabelRowBase - Abstract base class for label/value row display components
 * 
 * This abstract base class implements the Template Method pattern.
 * Subclasses must set $label and $data properties before calling parent::__construct().
 * 
 * Example:
 * ```php
 * class TransType extends LabelRowBase {
 *     function __construct($bi_lineitem) {
 *         $this->label = "Trans Type:";
 *         $this->data = "Credit"; // Derived from $bi_lineitem
 *         parent::__construct("");
 *     }
 * }
 * ```
 * 
 * @package Ksfraser\HTML
 * @since 20251019 - Fixed namespace (Html→HTML), fixed missing return, added return type hints
 * @version 20251019.1 - Fixed HtmlRowLabel→HTML_ROW_LABEL, added PHPDoc
 * @version 20251019.2 - Made class abstract (cannot be instantiated directly)
 */
abstract class LabelRowBase implements HtmlElementInterface
{
	/**
	 * The underlying row component
	 * @var HTML_ROW_LABEL
	 */
	protected $row;
	
	/**
	 * The label text (must be set by subclass)
	 * @var string
	 */
	protected $label;
	
	/**
	 * The data/value text (must be set by subclass)
	 * @var string
	 */
	protected $data;
	
	/**
	 * Create a label row
	 * 
	 * NOTE: Subclasses MUST set $this->label and $this->data BEFORE calling parent::__construct()
	 * 
	 * @param mixed $bi_lineitem The line item data (parameter kept for backward compatibility)
	 * @throws Exception If label or data not set by subclass
	 */
	function __construct( $bi_lineitem )
	{
		// Check if properties are set (not just if they exist, but if they have values)
		// Using property_exists() is more accurate than isset() for this use case
		if( ! property_exists($this, 'data') || ! isset( $this->data ) )
		{
			throw new Exception( "data MUST be set by inheriting class!" );
		}
		if( ! property_exists($this, 'label') || ! isset( $this->label ) )
		{
			throw new Exception( "label MUST be set by inheriting class!" );
		}
		$this->row = new HTML_ROW_LABEL( $this->data, $this->label,  null, null );
	}
	
	/**
	 * Get the HTML as a string
	 * 
	 * @return string The HTML
	 */
	function getHtml(): string
	{
		return $this->row->getHtml();
	}
	
	/**
	 * Output the HTML directly to screen
	 * 
	 * @return void
	 */
	function toHtml(): void
	{
		$this->row->toHtml();
	}
}
