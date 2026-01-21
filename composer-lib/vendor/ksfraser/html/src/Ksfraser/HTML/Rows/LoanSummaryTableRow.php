<?php
namespace Ksfraser\HTML\Rows;

use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Cells\IdLoanTableCell;
use Ksfraser\HTML\Cells\EditableLoanBorrowerCell;
use Ksfraser\HTML\Cells\EditableLoanAmountCell;
use Ksfraser\HTML\Cells\EditableLoanStatusCell;
use Ksfraser\HTML\Buttons\ViewLoanActionButton;
use Ksfraser\HTML\Buttons\EditLoanActionButton;

/**
 * LoanSummaryTableRow - Builds loan summary table rows
 * 
 * SRP: Single responsibility of building a loan summary row from loan data.
 * Handles: Row structure and action buttons. Uses cell classes for content cells.
 * 
 * @package Ksfraser\HTML\Rows
 */
class LoanSummaryTableRow extends BaseTableRow {
    /**
     * Build loan summary row
     * 
     * @param object $loan Loan object with id, borrower, amount, status
     * @return TableRow
     */
    public function build(object $loan): TableRow {
        $row = (new TableRow())->addClass('data-row');
        
        $loanId = $loan->id ?? null;
        $rowPrefix = $this->rowId ?: "loan-{$loanId}";
        
        // ID cell - configuration encapsulated in specific cell class
        $row->append((new IdLoanTableCell())->build($loanId, $rowPrefix));
        
        // Editable cells - configuration encapsulated in specific cell classes
        $row->append((new EditableLoanBorrowerCell())->buildEditable($loan->borrower ?? null, $loanId, $rowPrefix));
        $row->append((new EditableLoanAmountCell())->buildEditable($loan->amount ?? null, $loanId, $rowPrefix));
        $row->append((new EditableLoanStatusCell())->buildEditable($loan->status ?? null, $loanId, $rowPrefix));
        
        // Actions cell
        $actionsCell = (new TableData())->addClass('actions-cell');
        $actionsDiv = (new Div())->addClass('action-buttons');
        
        // Action buttons - configuration encapsulated in specific button classes
        $actionsDiv->append((new ViewLoanActionButton())->build($loanId));
        $actionsDiv->append((new EditLoanActionButton())->build($loanId));
        
        $actionsCell->append($actionsDiv);
        $row->append($actionsCell);
        
        return $row;
    }
}
