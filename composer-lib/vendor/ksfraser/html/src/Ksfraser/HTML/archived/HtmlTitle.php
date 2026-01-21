<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlTitle extends HtmlElement
{
	//can have styles
	//Only belongs in the HEAD
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "title";
	}
}
