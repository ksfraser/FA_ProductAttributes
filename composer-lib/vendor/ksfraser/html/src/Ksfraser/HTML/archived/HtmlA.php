<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* HtmlA - Convenient wrapper for HtmlLink
*
* Creates: <a href="URL">LINK CONTENT</a>
*
* Simplified constructor that accepts URL and content directly.
* Content can be text, images, or any HtmlElement.
*
* USAGE EXAMPLES:
*
*   // Simple text link
*   $link = new HtmlA("https://example.com", "Visit Site");
*
*   // Link using URL as text (null content)
*   $link = new HtmlA("https://github.com");
*
*   // Link with image
*   $link = new HtmlA("/page", new HtmlImage("icon.png"));
*
*   // Link with formatted content
*   $link = new HtmlA("/page", new HtmlRawString("<strong>Bold</strong> Link"));
*
*   // Link with query parameters
*   $link = new HtmlA("/search", "Search");
*   $link->addParam("q", "test");
*   $link->setTarget("_blank");
*
* COMMON VALID CONTENT TYPES:
*   ✓ string - Auto-wrapped in HtmlString
*   ✓ null - Uses URL as link text
*   ✓ HtmlString - Escaped text content
*   ✓ HtmlRawString - Unescaped HTML content
*   ✓ HtmlImage - Image inside link
*   ✓ HtmlDiv, HtmlSpan - Container elements (ensure no nested links)
*
* INVALID CONTENT (will throw exception):
*   ✗ HtmlA, HtmlEmail, HtmlLink - Cannot nest links
*   ✗ Arrays, integers, objects - Must be string or HtmlElementInterface
*/
class HtmlA extends HtmlLink
{
	/**
	 * Create a link with URL and content in one step
	 * 
	 * @param string $url The URL to link to
	 * @param HtmlElementInterface|string|null $linkContent The content to display.
	 *        - HtmlElementInterface: most HTML elements (text, image, div, span, etc.)
	 *        - string: will be wrapped in HtmlString
	 *        - null: URL will be used as link text
	 * 
	 * HTML5 allows most content inside <a> tags EXCEPT nested <a> tags.
	 * This constructor validates direct nesting only - it cannot detect nested
	 * links inside complex elements (e.g., HtmlDiv containing HtmlA).
	 * 
	 * Valid examples: HtmlString, HtmlRawString, HtmlImage, HtmlDiv, HtmlSpan
	 * Invalid: HtmlA, HtmlEmail, HtmlLink (direct nesting only)
	 * 
	 * @throws \Exception if content type is invalid or is a direct nested link
	 */
	function __construct( string $url, $linkContent = null )
	{
		// Handle different content types
		if( $linkContent === null )
		{
			// Use URL as link text
			$content = new HtmlString( $url );
		}
		elseif( is_string( $linkContent ) )
		{
			// Wrap string in HtmlString
			$content = new HtmlString( $linkContent );
		}
		elseif( $linkContent instanceof HtmlElementInterface )
		{
			// Validate that content is not a direct link (nested links are invalid in HTML)
			// Note: This only catches direct nesting. Cannot detect links nested inside
			// complex elements without deep tree traversal.
			if( $linkContent instanceof HtmlLink || 
			    $linkContent instanceof HtmlA || 
			    $linkContent instanceof HtmlEmail )
			{
				throw new \Exception( 
					"Invalid link content: Cannot nest links inside links. " .
					"Nested <a> tags are not allowed in HTML. " .
					"Content type: " . get_class( $linkContent )
				);
			}
			
			// HTML5 allows most other elements inside <a> tags
			// Common valid examples: HtmlString, HtmlRawString, HtmlImage, HtmlDiv, HtmlSpan
			// Developer responsibility: Ensure complex elements don't contain nested links
			$content = $linkContent;
		}
		else
		{
			throw new \Exception( 
				"Invalid link content type. Expected HtmlElementInterface, string, or null. Got: " . 
				gettype( $linkContent ) 
			);
		}
		
		// Initialize parent with content
		parent::__construct( $content );
		
		// Set the href
		$this->addHref( $url );
	}
}
