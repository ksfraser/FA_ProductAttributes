<?php

namespace Ksfraser\HTML\HTMLAtomic;

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
*/
class HtmlStyleList implements HtmlElementInterface
{
	protected $styleArray;
        function __constructor( HtmlStyle $style )
        {
                $this->addStyle( $style );
        }
        function addStyle( HtmlStyle $style )
        {
                $this->styleArray[] = $style;
        }
        public function toHtml() {
                echo $this->getHtml();
        }
 
	function getHtml()
	{
		if( count( $this->styleArray ) > 0 )
		{
			$html = 'style="';
		}
		else
		{
			return "";
		}
		foreach( $this->styleArray as $style )
		{
			$html .= $style->getHtml();
		}
		$html .= '"';
		return $html;
	}
}
