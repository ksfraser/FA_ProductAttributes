<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

use Ksfraser\HTML\HtmlAttribute;

/**//****************************
* Links 
*
* <a href="URL">TEXT</a>
*/
class HtmlLink extends HtmlElement
{
	//can have styles, title
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "a";
	}
	function addHref( $url, $text = "" )
	{
		if( is_object( $text ) )
		{
			// If text is an object, use it as nested content
			$this->nested = array();
			$this->addNested( $text );
		}
		else
		if( is_string( $text) AND strlen( $text ) > 0 )
		{
			// If text is a non-empty string, create HtmlString and nest it
			$this->nested = array();
			$this->addNested( new HtmlString( $text ) );
		}
		else
		{
			throw new \Exception( "An invalid HREF was passed in!" );
		}
		$this->addAttribute( new HtmlAttribute( "href", $url ) );
	}
	function setTarget( $target )
	{
		//Target can be _self, _blank, _parent, _top
		switch( $target )
		{
			case '_self':
			case '_blank':
			case '_parent':
			case '_top':
				$this->addAttribute( new HtmlAttribute( "target", $target ) );
				break;
			default:
				throw new \Exception( "Target type not recognized: $target" );
		}
		return;
	}

}
class HtmlA extends HtmlLink
{
}
class HtmlEmail extends HtmlLink
{
	//we could validate the validity of the email address
}
