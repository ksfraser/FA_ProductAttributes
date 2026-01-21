<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlTableHeaderCell extends HtmlElement
{
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "th";
	}
}
