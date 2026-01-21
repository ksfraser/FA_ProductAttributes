<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlHr extends HtmlEmptyElement
{
	function __construct( $data = "" )
	{
		parent::__construct( "" );
		$this->tag = "hr";
	}
}
