<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlTableHeaderCell extends HtmlElement
{
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "th";
	}
}
