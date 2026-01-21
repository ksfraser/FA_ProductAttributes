<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

class VIEW_DATE_CELL extends VIEW_CELL
{
	protected $date;
	function __construct()
	{
		parent::__construct();
	}
	function __toString()	
	{
		date_cells( 
				_($this->get( "label" ) ),
				$this->get( "f1" ),
				$this->get( "f2" ),
				$this->get( "f3" ),
				$this->get( "f4" )
			);
	}

    /**
     * Set the date with validation.
     *
     * @param string $date The date to set.
     * @throws InvalidArgumentException If the date is not in a valid format.
     */
    public function set_date($date)
    {
        if (!strtotime($date)) {
            throw new InvalidArgumentException("Invalid date format.");
        }

        $this->date = $date;
    }
}
