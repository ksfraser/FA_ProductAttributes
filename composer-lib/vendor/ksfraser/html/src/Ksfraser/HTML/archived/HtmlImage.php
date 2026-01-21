<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlImage extends HtmlElement
{
	//can have style, alt.  MUST HAVE src
	//  Width and Height can be either part of Style, or attributes themselves
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "img";
	}
	function setSrc( $src )
	{
		$this->addAttribute( new HtmlAttribute( "src", $src ) );
	}
	function setAlt( $alt )
	{
		$this->addAttribute( new HtmlAttribute( "alt", $alt ) );
	}
	// Either attributes or STYLE can be used to set the size
	//	However stylesheets can be used to change the size
	//	except when we use STYLE (see set size below)
	function setHeight( $height )
	{
		$this->addAttribute( new HtmlAttribute( "height", $height ) );
	}
	function setWidth( $width )
	{
		$this->addAttribute( new HtmlAttribute( "width", $width ) );
	}
	function setSize( int $width, int $height )
	{
		$w = new HtmlStyle( "width", $width . "px" );
		$h = new HtmlStyle( "height", $height . "px" );
		$s = new HtmlStyleList( $w );
		$s->addAttribute( $h );
		$this->addAttribute( 'style', $s );
	}
}
