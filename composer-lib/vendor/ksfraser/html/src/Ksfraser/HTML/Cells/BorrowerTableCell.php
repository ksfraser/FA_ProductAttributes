<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * BorrowerTableCell - Builds Borrower cells for loan tables
 * 
 * SRP: Single responsibility of formatting Borrower cells consistently.
 * Handles: Borrower cell styling, HTML escaping, N/A fallback.
 * 
 * @package Ksfraser\HTML\Cells
 */
class BorrowerTableCell extends BaseTableCell {
    /**
     * Build Borrower cell
     * 
     * @param mixed $borrower The borrower value
     * @return TableData
     */
    public function build($borrower): TableData {
        $cell = (new TableData())
            ->addClass('borrower-cell')
            ->setText(htmlspecialchars($borrower ?? ''));
        
        return $this->applyAttributes($cell);
    }
}
