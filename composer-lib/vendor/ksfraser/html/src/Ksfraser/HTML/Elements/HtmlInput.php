<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlEmptyElement;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Base class for generating HTML <input> elements
 *
 * @link https://www.w3schools.com/tags/tag_input.asp
 * @since 20251019
 *
 * Input elements can have the following attributes:
 * - accept (FILE ONLY)
 * - alt (text)
 * - autocomplete (on/off)
 * - autofocus
 * - checked (checkbox or RadioButton)
 * - dirname
 * - disabled
 * - form (form_id)
 * - formaction (submit or image - URL)
 * - formenctype (submit, image):
 *   - application/x-www-form-urlencoded
 *   - multipart/form-data
 *   - text/plain
 * - method (dialog, get, post)
 * - name
 * - novalidate
 * - rel (external, help, license, next, nofollow, noopener, noreferrer, opener, prev, search)
 * - target (_blank, _self, _parent, _top)
 * - type (text, password, submit, reset, button, etc.)
 * - value
 *
 * @package Ksfraser\HTML
 */
class HtmlInput extends HtmlEmptyElement
{
	/**
	 * The type attribute for the input element
	 * @var string
	 */
	protected $type;

	/**
	 * Constructor for HtmlInput
	 *
	 * @param string $type The input type (text, password, email, etc.)
	 */
	function __construct( string $type = "text" )
	{
		parent::__construct();
		$this->tag = "input";
		$this->type = $type;
		$this->addAttribute( new HtmlAttribute( "type", $type ) );
	}

	/**
	 * Set the name attribute
	 *
	 * @param string $name The name attribute value
	 * @return self Fluent interface
	 */
	public function setName( string $name ): self
	{
		$this->addAttribute( new HtmlAttribute( "name", $name ) );
		return $this;
	}

	/**
	 * Set the value attribute
	 *
	 * @param string $value The value attribute
	 * @return self Fluent interface
	 */
	public function setValue( string $value ): self
	{
		$this->addAttribute( new HtmlAttribute( "value", htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) ) );
		return $this;
	}

	/**
	 * Set the placeholder attribute
	 *
	 * @param string $placeholder The placeholder text
	 * @return self Fluent interface
	 */
	public function setPlaceholder( string $placeholder ): self
	{
		$this->addAttribute( new HtmlAttribute( "placeholder", htmlspecialchars( $placeholder, ENT_QUOTES, 'UTF-8' ) ) );
		return $this;
	}
}
