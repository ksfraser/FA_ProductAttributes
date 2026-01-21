<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * DescriptionTableCell - Builds reusable Description cells for any table
 * 
 * SRP: Single responsibility of formatting Description cells consistently.
 * Handles: Description cell styling, HTML escaping, N/A fallback.
 * 
 * @package Ksfraser\HTML\Cells
 */
class DescriptionTableCell extends BaseTableCell {
    /**
     * Build Description cell
     * 
     * @param mixed $description The description value
     * @return TableData
     */
    public function build($description): TableData {
        $cell = (new TableData())
            ->addClass('description-cell')
            ->setText(htmlspecialchars($description ?? ''));
        
        return $this->applyAttributes($cell);
    }
}
