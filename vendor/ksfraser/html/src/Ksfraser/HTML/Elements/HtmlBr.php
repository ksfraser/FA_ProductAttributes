<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlEmptyElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlBr extends HtmlEmptyElement
{
	function __construct( $data = "" )
	{
		parent::__construct( "" );
		$this->tag = "br";
	}
}
