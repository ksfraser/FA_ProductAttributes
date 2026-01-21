<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* Links 
*
* <a href="URL">TEXT</a>
*/
class HtmlLink extends HtmlElement
{
	//can have styles, title
	protected $baseUrl;
	protected $params = [];
	
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "a";
	}
	function addHref( $url, $text = "" )
	{
		if( is_object( $text ) )
		{
		}
		else
		if( is_string( $text) AND strlen( $text ) > 0 )
		{
			$this->data = new HtmlString( $text );
		}
		else
		{
			throw new Exception( "An invalid HREF was passed in!" );
		}
		$this->baseUrl = $url;
		$this->updateHrefAttribute();
	}
	
	/**
	 * Add a single query parameter to the URL
	 * @param string $key Parameter name
	 * @param string $value Parameter value
	 */
	function addParam( string $key, string $value )
	{
		$this->params[$key] = $value;
		$this->updateHrefAttribute();
	}
	
	/**
	 * Set multiple query parameters at once
	 * @param array $params Associative array of key => value pairs
	 */
	function setParams( array $params )
	{
		foreach( $params as $key => $value )
		{
			$this->params[$key] = $value;
		}
		$this->updateHrefAttribute();
	}
	
	/**
	 * Update the href attribute with base URL and parameters
	 */
	protected function updateHrefAttribute()
	{
		if( empty( $this->baseUrl ) )
		{
			return;
		}
		
		$fullUrl = $this->baseUrl;
		
		if( count( $this->params ) > 0 )
		{
			$fullUrl .= "?" . http_build_query( $this->params );
		}
		
		// Remove existing href attribute if present
		$this->removeAttributeByName( "href" );
		$this->addAttribute( new HtmlAttribute( "href", $fullUrl ) );
	}
	
	/**
	 * Remove an attribute by name (helper for updateHrefAttribute)
	 */
	protected function removeAttributeByName( string $name )
	{
		if( isset( $this->attributes ) && is_array( $this->attributes ) )
		{
			foreach( $this->attributes as $key => $attr )
			{
				if( $attr->getName() === $name )
				{
					unset( $this->attributes[$key] );
					return;
				}
			}
		}
	}
	
	function setTarget( $target )
	{
		//Target can be _self, _blank, _parent, _top
		switch( $target )
		{
			case '_self':
			case '_blank':
			case '_parent':
			case '_top':
				$this->addAttribute( new HtmlAttribute( "target", $target ) );
				break;
			default:
				throw new Exception( "Target type not recognized: $target" );
		}
		return;
	}

}
