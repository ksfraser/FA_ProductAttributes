<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * EditableFrequencyNameCell - Encapsulates name cell configuration for frequency rows
 * 
 * SRP: Encapsulate the specific configuration for editable name cells in frequency tables.
 * Takes variable parts (freqId, rowPrefix, value) and applies fixed configuration.
 * 
 * @package Ksfraser\HTML\Cells
 */
class EditableFrequencyNameCell extends NameTableCell {
    /**
     * Build editable frequency name cell
     * 
     * @param mixed $value The name value
     * @param mixed $freqId The frequency ID
     * @param string $rowPrefix The row prefix for cell ID generation
     * @return TableData
     */
    public function buildEditable($value, $freqId, string $rowPrefix): TableData {
        $this->setId("name-cell-{$rowPrefix}")
            ->setAttribute('data-freq-id', (string)$freqId)
            ->setAttribute('data-field', 'name')
            ->setDblClickHandler('editFreqCell(this)')
            ->setEditable(true);
        
        return parent::build($value);
    }
}
