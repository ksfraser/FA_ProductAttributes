<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

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
