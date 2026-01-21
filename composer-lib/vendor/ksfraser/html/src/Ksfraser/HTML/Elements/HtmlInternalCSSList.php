<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

class HtmlInternalCSSList extends HtmlStyleList 
{
 
	function getHtml()
	{
		if( count( $this->styleArray ) > 0 )
		{
			$html = $this->tag . '{';
		}
		else
		{
			return "";
		}
		foreach( $this->styleArray as $style )
		{
			$html .= $style->getHtml();
		}
		$html .= '}\n\r';
		return $html;
	}
}
