<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* Email Links
*
* Creates: <a href="mailto:email@example.com">LINK TEXT</a>
*
* Email addresses are validated and automatically prefixed with "mailto:"
* Extends HtmlA since email links are just specialized anchor tags
*/
class HtmlEmail extends HtmlA
{
	/**
	 * Create an email link
	 * 
	 * @param string $emailAddress The email address (without mailto:)
	 * @param HtmlElementInterface|string|null $linkContent The content to display. 
	 *        - HtmlElementInterface: any HTML element (text, image, etc.)
	 *        - string: will be wrapped in HtmlString
	 *        - null: email address will be used as link text
	 * @param bool $validateEmail Whether to validate the email format (default: true)
	 * @throws \Exception if email is invalid or content type is invalid
	 */
	function __construct( string $emailAddress, $linkContent = null, bool $validateEmail = true )
	{
		// Validate email address if requested
		if( $validateEmail && !filter_var( $emailAddress, FILTER_VALIDATE_EMAIL ) )
		{
			throw new \Exception( "Invalid email address: $emailAddress" );
		}
		
		// Build mailto URL
		$mailtoUrl = "mailto:" . $emailAddress;
		
		// If no content provided, use email address as link text
		if( $linkContent === null )
		{
			$linkContent = $emailAddress;
		}
		
		// Call parent HtmlA constructor - it handles string/HtmlElementInterface/null
		parent::__construct( $mailtoUrl, $linkContent );
	}
}
