<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * EditableLoanAmountCell - Encapsulates amount cell configuration for loan rows
 * 
 * SRP: Encapsulate the specific configuration for editable amount cells in loan summaries.
 * Takes variable parts (loanId, rowPrefix, value) and applies fixed configuration.
 * 
 * @package Ksfraser\HTML\Cells
 */
class EditableLoanAmountCell extends AmountTableCell {
    /**
     * Build editable loan amount cell
     * 
     * @param mixed $value The amount value
     * @param mixed $loanId The loan ID
     * @param string $rowPrefix The row prefix for cell ID generation
     * @return TableData
     */
    public function buildEditable($value, $loanId, string $rowPrefix): TableData {
        $this->setId("amount-cell-{$rowPrefix}")
            ->setAttribute('data-loan-id', (string)$loanId)
            ->setAttribute('data-field', 'amount')
            ->setDblClickHandler('editLoanCell(this)')
            ->setEditable(true);
        
        return parent::build($value);
    }
}
