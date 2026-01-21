<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;


class VIEW_AMOUNT_CELL extends VIEW_CELL
{
	protected $amount;
	function __construct( $f1 = "" )
	{
		parent::__construct();
		$this->set( "f1", $f1 );
	}
	function __toString()	
	{
		amount_cells( 
				$this->get( "f1" ),
			);
	}
    /**
     * Set the amount with validation.
     *
     * @param float $amount The amount to set.
     * @throws InvalidArgumentException If the amount is not a valid number.
     */
    public function set_amount($amount)
    {
        if (!is_numeric($amount) || $amount < 0) {
            throw new InvalidArgumentException("Amount must be a non-negative number.");
        }

        $this->amount = $amount;
    }
}

