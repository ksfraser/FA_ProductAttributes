<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * IdFrequencyTableCell - Encapsulates interest frequency ID cell configuration
 * 
 * SRP: Single responsibility of building a frequency ID cell with fixed configuration.
 * Applies: ID format, data-frequency-id attribute, and cell type.
 * 
 * @package Ksfraser\HTML\Cells
 */
class IdFrequencyTableCell {
    /**
     * Build frequency ID cell with fixed configuration
     * 
     * @param mixed $frequencyId The frequency ID value
     * @param string $rowPrefix The row prefix for ID generation
     * @return TableData
     */
    public function build($frequencyId, string $rowPrefix): TableData {
        return (new IdTableCell())
            ->setId("id-cell-{$rowPrefix}")
            ->setAttribute('data-frequency-id', (string)$frequencyId)
            ->build($frequencyId);
    }
}
