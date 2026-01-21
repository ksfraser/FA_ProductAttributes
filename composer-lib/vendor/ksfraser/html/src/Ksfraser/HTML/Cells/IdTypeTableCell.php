<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * IdTypeTableCell - Encapsulates loan type ID cell configuration
 * 
 * SRP: Single responsibility of building a type ID cell with fixed configuration.
 * Applies: ID format, data-type-id attribute, and cell type.
 * 
 * @package Ksfraser\HTML\Cells
 */
class IdTypeTableCell {
    /**
     * Build type ID cell with fixed configuration
     * 
     * @param mixed $typeId The type ID value
     * @param string $rowPrefix The row prefix for ID generation
     * @return TableData
     */
    public function build($typeId, string $rowPrefix): TableData {
        return (new IdTableCell())
            ->setId("id-cell-{$rowPrefix}")
            ->setAttribute('data-type-id', (string)$typeId)
            ->build($typeId);
    }
}
