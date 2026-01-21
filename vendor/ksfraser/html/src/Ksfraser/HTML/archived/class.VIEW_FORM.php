<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

class VIEW_FORM extends origin
{
	protected $form_item_array;
	function __construct(  )
	{
		parent::__construct();
		$this->form_item_array = array();
	}
	function __toString()
	{
		$this->start_form();
		foreach( $this->form_item_array as $obj )
		{
			echo $obj;
		}
		$this->end_form();
	}
	function start_form()
	{
		start_form();
	}
	function end_form()
	{
		end_form();
	}
    /**
     * Add an item to the form with validation.
     *
     * @param object $item The item to add.
     * @throws InvalidArgumentException If the item is not an object.
     */
    public function add_item($item)
    {
        if (!is_object($item)) {
            throw new InvalidArgumentException("Item must be an object.");
        }

        $this->form_item_array[] = $item;
    }
}
