<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//*********************************************
* Formatting on Text.
*
* @since 20250517
*/
class HtmlFormatting extends HtmlElement
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "";
	}
	function addAttribute( HtmlAttribute $attribute )
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
	function setAttributeList( HtmlAttributeList $list )
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
	function newAttributeList()
	{
		throw new Exception( "Does HTML Formatting allow Attributes?" );
	}
}
