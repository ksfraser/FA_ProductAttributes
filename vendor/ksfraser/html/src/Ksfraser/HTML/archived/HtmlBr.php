<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlBr extends HtmlEmptyElement
{
	function __construct( $data = "" )
	{
		parent::__construct( "" );
		$this->tag = "br";
	}
}
