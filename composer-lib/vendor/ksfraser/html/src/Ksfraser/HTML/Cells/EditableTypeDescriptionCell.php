<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * EditableTypeDescriptionCell - Encapsulates description cell configuration for loan type rows
 * 
 * SRP: Encapsulate the specific configuration for editable description cells in loan type tables.
 * Takes variable parts (typeId, rowPrefix, value) and applies fixed configuration.
 * 
 * @package Ksfraser\HTML\Cells
 */
class EditableTypeDescriptionCell extends DescriptionTableCell {
    /**
     * Build editable type description cell
     * 
     * @param mixed $value The description value
     * @param mixed $typeId The type ID
     * @param string $rowPrefix The row prefix for cell ID generation
     * @return TableData
     */
    public function buildEditable($value, $typeId, string $rowPrefix): TableData {
        $this->setId("description-cell-{$rowPrefix}")
            ->setAttribute('data-type-id', (string)$typeId)
            ->setAttribute('data-field', 'description')
            ->setDblClickHandler('editTypeCell(this)')
            ->setEditable(true);
        
        return parent::build($value);
    }
}
