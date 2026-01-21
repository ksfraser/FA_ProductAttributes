<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;


class VIEW_CELL extends origin
{
	protected $label;
	protected $f1;
	protected $f2;
	protected $f3;
	protected $f4;
	function __construct()
	{
		parent::__construct();
	}
	function __toString()	
	{
	}

    /**
     * Set the value with validation.
     *
     * @param mixed $value The value to set.
     * @throws InvalidArgumentException If the value is invalid.
     */
    public function set_value($value)
    {
        if (is_null($value)) {
            throw new InvalidArgumentException("Value cannot be null.");
        }

        $this->value = $value;
    }
}
