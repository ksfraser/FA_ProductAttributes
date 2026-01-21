<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**//*************************************************
* Class for generating forms
*
*	https://www.w3schools.com/tags/tag_form.asp
*
* Forms can have the following elements:
*	input
*	textarea
*	button
*	select
*	option
*	optgroup
*	fieldset
*	label
*	output
*
* Forms can have the following attributes
*	accept-charset
*	action
*	autocomplete (on/off)
*	enctype
*	method (dialog, get, post)
*	name
*	novalidate
*	rel
*		external
*		help
*		license
*		next
*		nofollow
*		noopener
*		noreferrer
*		opener
*		prev
*		search
*	target
*		_blank
*		_self
*		_parent
*		_top
*******************************************************/
class HtmlForm extends HtmlElement
{
	//can have styles
	protected $action;	//URL
	protected $method;	//get or post
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "form";
	}
}
