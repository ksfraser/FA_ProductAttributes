<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlTableCol extends HtmlElement
{
	//specify column properties within a column group
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "col";
	}
}
