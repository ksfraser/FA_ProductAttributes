<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * NameTableCell - Builds reusable Name cells for any table
 * 
 * SRP: Single responsibility of formatting Name cells consistently.
 * Handles: Name cell styling, HTML escaping, N/A fallback.
 * 
 * @package Ksfraser\HTML\Cells
 */
class NameTableCell extends BaseTableCell {
    /**
     * Build Name cell
     * 
     * @param mixed $name The name value
     * @return TableData
     */
    public function build($name): TableData {
        $cell = (new TableData())
            ->addClass('name-cell')
            ->setText(htmlspecialchars($name ?? ''));
        
        return $this->applyAttributes($cell);
    }
}
