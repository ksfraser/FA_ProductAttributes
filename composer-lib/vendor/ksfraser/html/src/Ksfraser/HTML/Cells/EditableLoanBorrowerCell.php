<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * EditableLoanBorrowerCell - Encapsulates borrower cell configuration for loan rows
 * 
 * SRP: Encapsulate the specific configuration for editable borrower cells in loan summaries.
 * Takes variable parts (loanId, rowPrefix, value) and applies fixed configuration.
 * 
 * @package Ksfraser\HTML\Cells
 */
class EditableLoanBorrowerCell extends BorrowerTableCell {
    /**
     * Build editable loan borrower cell
     * 
     * @param mixed $value The borrower value
     * @param mixed $loanId The loan ID
     * @param string $rowPrefix The row prefix for cell ID generation
     * @return TableData
     */
    public function buildEditable($value, $loanId, string $rowPrefix): TableData {
        $this->setId("borrower-cell-{$rowPrefix}")
            ->setAttribute('data-loan-id', (string)$loanId)
            ->setAttribute('data-field', 'borrower')
            ->setDblClickHandler('editLoanCell(this)')
            ->setEditable(true);
        
        return parent::build($value);
    }
}
