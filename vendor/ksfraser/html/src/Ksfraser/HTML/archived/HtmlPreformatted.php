<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlPreformatted extends HtmlElement
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "pre";
	}
}
