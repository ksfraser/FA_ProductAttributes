<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * IdLoanTableCell - Encapsulates loan ID cell configuration
 * 
 * SRP: Single responsibility of building a loan ID cell with fixed configuration.
 * Applies: ID format, data-loan-id attribute, and cell type.
 * 
 * @package Ksfraser\HTML\Cells
 */
class IdLoanTableCell {
    /**
     * Build loan ID cell with fixed configuration
     * 
     * @param mixed $loanId The loan ID value
     * @param string $rowPrefix The row prefix for ID generation
     * @return TableData
     */
    public function build($loanId, string $rowPrefix): TableData {
        return (new IdTableCell())
            ->setId("id-cell-{$rowPrefix}")
            ->setAttribute('data-loan-id', (string)$loanId)
            ->build($loanId);
    }
}
