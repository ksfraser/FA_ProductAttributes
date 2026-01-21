<?php

namespace Ksfraser\HTML;

use Ksfraser\Origin\origin;

class VIEW_TABLE extends origin
{
	protected $table_headers;
	protected $table_rows;
	protected $table_class;
	function __construct()
	{
		parent::__construct();
		$this->table_rows = array();
		$this->table_headers = array();
		$this->set_var( "table_width", "70" );
		$this->set( "table_style", TABLESTYLE );
	}
	function __toString()
	{
		$this->start_table();
		foreach( $this->table_headers as $obj )
		{
			echo $obj;
		}
		foreach( $this->table_rows as $obj )
		{
			echo $obj;
		}
		$this->end_table();
	}
	function end_table()
	{
		end_table();
	}
	/**//***********************
	* Create a new Table
	*
	* Normally would do sanity check on variables but they are set in the constructor.
	*
	**************************/
	function start_table()
	{
		start_table( $this->get( "table_style" ), "width=" . $this->get( "table_width" ) . "%");
	}
	/**//***********************************************************
	* Setting with data validation
	*
	****************************************************************/
	function set( $var, $value = null, $enforce = true )
	{
		switch( $var )
		{
			case "style_width":
				if( is_integer( $value ) )
				{
					//good start
				}
				else
				{
					//try to convert data type
					$tmp = (int)$value;
					if( is_integer( $tmp ) )
					{
						if( $tmp <> $value )
						{
							return FALSE;
						}
						$value = $tmp;
					}
				}
			default:
				parent::set( $var, $value, $enforce);
				break;
		}
	}
    /**
     * Add a row to the table with validation.
     *
     * @param array $row The row to add.
     * @throws InvalidArgumentException If the row is not an array.
     */
    public function add_row($row)
    {
        if (!is_array($row)) {
            throw new InvalidArgumentException("Row must be an array.");
        }

        $this->table_rows[] = $row;
    }
    /**
     * Generate the HTML output for the table.
     *
     * @return string The HTML representation of the table.
     */
    public function toHtml()
    {
        $html = '';
        $html .= $this->start_table();
        foreach ($this->table_headers as $header) {
            $html .= $header->toHtml();
        }
        foreach ($this->table_rows as $row) {
            $html .= $row->toHtml();
        }
        $html .= $this->end_table();

        // Check for empty content and return self-closing tag if applicable
        return trim($html) === '' ? '<table />' : $html;
    }
}
