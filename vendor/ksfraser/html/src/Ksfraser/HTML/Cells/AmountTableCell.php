<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * AmountTableCell - Builds Amount cells for loan tables
 * 
 * SRP: Single responsibility of formatting Amount cells consistently.
 * Handles: Currency formatting, cell styling, N/A fallback.
 * 
 * @package Ksfraser\HTML\Cells
 */
class AmountTableCell extends BaseTableCell {
    /**
     * Build Amount cell with currency formatting
     * 
     * @param mixed $amount The amount value
     * @return TableData
     */
    public function build($amount): TableData {
        $amountText = isset($amount) 
            ? '$' . number_format((float)$amount, 2)
            : 'N/A';
            
        $cell = (new TableData())
            ->addClass('amount-cell')
            ->setText($amountText);
        
        return $this->applyAttributes($cell);
    }
}
