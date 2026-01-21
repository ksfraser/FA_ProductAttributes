<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlPreformatted extends HtmlElement
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "pre";
	}
}
