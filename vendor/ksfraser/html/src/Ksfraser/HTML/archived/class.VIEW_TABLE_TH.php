<?php

require_once( 'class.origin.php' );
class VIEW_TABLE_TH extends origin
{
	protected $th_item;
	function __construct( $value = "" )
	{
		parent::__construct();
		$this->set( "th_item", $value );
	}
	function __toString()
	{
		$this->start_th();
		echo $this->th_item;
		$this->end_th();
	}
	function start_th()
	{
		echo "<th>";
	}
	function end_th()
	{
		echo "</th>";
	}
}
