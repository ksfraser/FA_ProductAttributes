<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

require_once( 'class.VIEW_CELL.php' );
class VIEW_SUBMIT_CELL extends VIEW_CELL
{
//aspect='default'  name="RefreshInquiry"  id="RefreshInquiry" value="Search" title='Refresh Inquiry'
	protected $name;
	protected $id;
	protected $value;
	protected $title;
	protected $aspect;
	protected $submit_label;
	protected $submit_action;
	function __construct()
	{
		parent::__construct();
	}
	function __toString()	
	{
		submit_cells( 
				$this->get( "id" ),
				$this->get( "value" ),
				$this->get( "title" ),
				$this->get( "aspect" )
			);
	}
    /**
     * Set the submit label with validation.
     *
     * @param string $label The label to set.
     * @throws InvalidArgumentException If the label is not a string.
     */
    public function set_submit_label($label)
    {
        if (!is_string($label) || empty($label)) {
            throw new InvalidArgumentException("Label must be a non-empty string.");
        }

        $this->submit_label = $label;
    }
}

