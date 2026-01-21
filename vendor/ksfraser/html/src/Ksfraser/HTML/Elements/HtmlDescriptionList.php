<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlDescriptionList extends HtmlElement
{
	//Held within either an Ordered List or Unordered List
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "dl";
	}
}
