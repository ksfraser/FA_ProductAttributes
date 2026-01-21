<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************************
* Buttons use Javascript
*
* @since 20250517
*/
class HtmlButton extends HtmlElement
{
	//can have style, alt.  MUST HAVE src
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "button";
	}
	function setOnclick( HtnlAttribute $onclick )
	{
			//onclick="document.location='default.asp'"
		$this->addAttribute( new HtmlAttribute( "onclick", $onclick ) );
	}
}
