<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlItalic extends HtmlFormatting
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "i";
	}
}
