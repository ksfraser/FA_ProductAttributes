<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

class VIEW_DIV extends origin
{
	protected $name;
	protected $div_item_array;
	protected $content;
	function __construct( $name = "" )
	{
		parent::__construct();
		$this->div_item_array = array();
		$this->set( "name", $name );
	}
	function __toString()
	{
		$this->start_div();
		foreach( $this->div_item_array as $obj )
		{
			echo $obj;
		}
		$this->end_div();
	}
	function start_div()
	{
		start_div( $this->get( "name" ) );
	}
	function end_div()
	{
		end_div();
	}
    /**
     * Add content to the div with validation.
     *
     * @param string $content The content to add.
     * @throws InvalidArgumentException If the content is not a string.
     */
    public function add_content($content)
    {
        if (!is_string($content)) {
            throw new InvalidArgumentException("Content must be a string.");
        }

        $this->content .= $content;
    }
    /**
     * Generate the HTML output for the div.
     *
     * @return string The HTML representation of the div.
     */
    public function toHtml()
    {
        $html = '';
        $html .= $this->start_div();
        foreach ($this->div_item_array as $item) {
            $html .= $item->toHtml();
        }
        $html .= $this->end_div();

        // Check for empty content and return self-closing tag if applicable
        return trim($html) === '' ? '<div />' : $html;
    }
}
