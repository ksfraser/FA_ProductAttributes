<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * IdTableCell - Builds reusable ID cells for any table
 * 
 * SRP: Single responsibility of formatting ID cells consistently.
 * Handles: ID cell styling, conversion to string, N/A fallback.
 * 
 * @package Ksfraser\HTML\Cells
 */
class IdTableCell extends BaseTableCell {
    /**
     * Build ID cell
     * 
     * @param mixed $id The ID value
     * @return TableData
     */
    public function build($id): TableData {
        $cell = (new TableData())
            ->addClass('id-cell')
            ->setText((string)($id ?? 'N/A'));
        
        return $this->applyAttributes($cell);
    }
}
