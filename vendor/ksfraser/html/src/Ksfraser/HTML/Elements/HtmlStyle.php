<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;

/**//**
* A Style is an attribute with KEY Style and value param:setting
*
*	https://www.w3schools.com/html/html_styles.asp
*
*	Examples
*		background-color
*		color
*		font-family
*		font-size
*		text-align
*
*	This is INLINE CSS.  There is also Internial CSS
*/
class HtmlStyle extends HtmlAttribute
{
	function getHtml()
	{
		$html = $this->attribute . ':' . $this->value . ';';
		return $html;
	}
}
