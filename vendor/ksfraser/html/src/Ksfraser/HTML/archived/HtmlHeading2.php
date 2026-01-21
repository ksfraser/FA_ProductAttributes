<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* Headings can have 6 levels, styles.
*
* @since 20250517
*/
class HtmlHeading2 extends HtmlHeading
{
	function __construct( HtmlElementInterface $data )
	{
		parent::__construct( $data );
		$this->tag = "h2";
	}
}
