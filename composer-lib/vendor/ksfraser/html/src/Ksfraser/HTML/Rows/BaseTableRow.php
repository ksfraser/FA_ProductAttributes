<?php
namespace Ksfraser\HTML\Rows;

use Ksfraser\HTML\Elements\TableRow;

/**
 * BaseTableRow - Abstract base for domain-specific table row builders
 * 
 * SRP: Single responsibility of building domain-specific table rows.
 * Each subclass handles building a row for a specific domain object.
 * 
 * @package Ksfraser\HTML\Rows
 */
abstract class BaseTableRow {
    /**
     * @var string|null Optional row ID for cell referencing
     */
    protected ?string $rowId = null;
    
    /**
     * Set row ID for automatic cell ID generation
     * 
     * @param string $rowId The row ID (e.g., "loan-123")
     * @return self
     */
    public function setRowId(string $rowId): self {
        $this->rowId = $rowId;
        return $this;
    }
    
    /**
     * Build a table row from a domain object
     * 
     * @param object $data Domain object (Loan, LoanType, etc)
     * @return TableRow
     */
    abstract public function build(object $data): TableRow;
}
