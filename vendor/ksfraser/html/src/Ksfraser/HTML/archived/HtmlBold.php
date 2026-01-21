<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlBold extends HtmlFormatting
{
	function __construct( $data )
	{
		parent::__construct( $data );
		$this->tag = "b";
	}
}
class HtmlB extends HtmlBold
{
}
