<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * FaCell - FrontAccounting Table Cell Element
 *
 * Generates HTML table cell (td) with FA-specific functionality,
 * matching the output of label_cells and similar functions.
 */
class FaCell extends HtmlTd
{
    private $extra;

    /**
     * Constructor
     *
     * @param string $content Cell content
     * @param string $extra Additional attributes
     */
    public function __construct($content = "", $extra = "")
    {
        parent::__construct(new HtmlString($content));
        $this->extra = $extra;

        // Set attributes
        $this->setCellAttributes();
    }

    /**
     * Set cell attributes based on extra parameter
     */
    private function setCellAttributes()
    {
        if ($this->extra) {
            $this->parseExtraAttributes();
        }
    }

    /**
     * Parse extra attributes string
     */
    private function parseExtraAttributes()
    {
        // Simple parsing: assume format like "key='value' key2='value2'"
        $attrs = explode(' ', trim($this->extra));
        foreach ($attrs as $attr) {
            if (strpos($attr, '=') !== false) {
                list($key, $value) = explode('=', $attr, 2);
                $value = trim($value, "'\"");
                $this->attributeList->addAttribute(new HtmlAttribute($key, $value));
            }
        }
    }
}