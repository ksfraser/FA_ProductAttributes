<?php
namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * A trait that defines an object which can have HTML children
 * 
 * @author Kevin Fraser <kevin@ksfraser.ca>
 */
trait HTMLChildrenTrait {
	
	/**
	 * @var HtmlElementInterface[]
	 */
	protected $children = array();
	
	/**
	 * @return HtmlElementInterface[]
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * Sets the list of children of this element.
	 * 
	 * @param HtmlElementInterface[] $children
	 * @return $this
	 */
	public function setChildren(array $children) {
		foreach( $children as $child )
		{
			$this->addChild( $child );
		}
		return $this;
	}
	
	/**
	 * Adds a child to this element.
	 * 
	 * @param HtmlElementInterface $child
	 * @return \Mouf\Html\Tags\ChildrenTrait
	 */
	public function addChild(HtmlElementInterface $child) {
		$this->children[] = $child;
		return $this;
	}
	
	/**
	 * Renders HTML attributes.
	 * 
	 * @return string
	 */
	protected function renderChildren() {
		ob_start();
		foreach ($this->children as $child) {
			if ($child != null) {
				$child->toHtml();
			}
		}
		return ob_get_clean();
	}
	protected function renderChildrenHtml()
	{
		$html = "";
		foreach ($this->children as $child) {
			if ($child != null) {
				$html .= $child->getHtml();
			}
		}
		return $html;
	}
}
