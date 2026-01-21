<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * EditableLoanStatusCell - Encapsulates status cell configuration for loan rows
 * 
 * SRP: Encapsulate the specific configuration for editable status cells in loan summaries.
 * Takes variable parts (loanId, rowPrefix, value) and applies fixed configuration.
 * 
 * @package Ksfraser\HTML\Cells
 */
class EditableLoanStatusCell extends StatusTableCell {
    /**
     * Build editable loan status cell
     * 
     * @param mixed $value The status value
     * @param mixed $loanId The loan ID
     * @param string $rowPrefix The row prefix for cell ID generation
     * @return TableData
     */
    public function buildEditable($value, $loanId, string $rowPrefix): TableData {
        $this->setId("status-cell-{$rowPrefix}")
            ->setAttribute('data-loan-id', (string)$loanId)
            ->setAttribute('data-field', 'status')
            ->setDblClickHandler('editLoanCell(this)')
            ->setEditable(true);
        
        return parent::build($value);
    }
}
