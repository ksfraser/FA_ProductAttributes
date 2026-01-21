<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlMark extends HtmlFormatting
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "mark";
	}
}
