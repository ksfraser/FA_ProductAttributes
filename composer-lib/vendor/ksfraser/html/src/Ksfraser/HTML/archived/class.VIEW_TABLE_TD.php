<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

class VIEW_TABLE_TD extends origin
{
	protected $td_content;
	protected $td_class;
	function __construct( $value = "" )
	{
		parent::__construct();
		$this->set( "td_item", $value );
	}
	function __toString()
	{
		$this->start_td();
		echo $this->td_item;
		$this->end_td();
	}
	function start_td()
	{
		echo "<td>";
	}
	function end_td()
	{
		echo "</td>";
	}
    /**
     * Set the content of the table cell with validation.
     *
     * @param string $content The content to set.
     * @throws InvalidArgumentException If the content is not a string.
     */
    public function set_content($content)
    {
        if (!is_string($content)) {
            throw new InvalidArgumentException("Content must be a string.");
        }

        $this->td_content = $content;
    }
}
