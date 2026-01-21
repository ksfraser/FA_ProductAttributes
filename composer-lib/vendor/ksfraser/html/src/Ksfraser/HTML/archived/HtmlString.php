<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlString implements HtmlElementInterface {
	protected $string;
	function __construct( $string )
	{
		$this->string = $string;
	}
	/**
	 * Renders the object in HTML.
	 * The Html is echoed directly into the output.
	 */
	public function toHtml() {
		echo $this->getHtml();
	}
	public function getHtml()
	{
		//A HTML string doesn't have tags, attributes, styles, etc.
		$html = $this->string;
		return $html;
	}
}
