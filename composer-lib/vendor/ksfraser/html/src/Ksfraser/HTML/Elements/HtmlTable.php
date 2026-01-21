<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;

/***********************
* Tables can have the following CSS elements:
*	Borders
*	Sizes
*	Headers
*	Padding and Spacing
*	Colspan and Rowspan
*	Styling
*	Colgroup
*/
class HtmlTable extends HtmlElement
{
	//can have styles
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "table";
	}

	/**
	 * Create an FA-styled table
	 *
	 * @param int $faStyle FA table style constant (TABLESTYLE, TABLESTYLE2, etc.)
	 * @param string $extra Additional attributes
	 * @return FaTable
	 */
	public static function createFaTable($faStyle = 2, $extra = "")
	{
		return new FaTable($faStyle, $extra);
	}
}
