<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

class VIEW_ROW extends origin
{
	protected $row_data;
	protected $row_class;
	function __construct()
	{
		parent::__construct();
		$this->row_item_array = array();
	}
	function __toString()
	{
		$this->start_row();
		foreach( $this->row_item_array as $obj )
		{
			echo $obj;
		}
		$this->end_row();
	}
	function start_row()
	{
		start_row();
	}
	function end_row()
	{
		end_row();
	}
    /**
     * Add an item to the row with validation.
     *
     * @param object $item The item to add.
     * @throws InvalidArgumentException If the item is not an object.
     */
    public function add_item($item)
    {
        if (!is_object($item)) {
            throw new InvalidArgumentException("Item must be an object.");
        }

        $this->row_item_array[] = $item;
    }
}
