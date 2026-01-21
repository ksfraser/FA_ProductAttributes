<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * EditableTypeNameCell - Encapsulates name cell configuration for loan type rows
 * 
 * SRP: Encapsulate the specific configuration for editable name cells in loan type tables.
 * Takes variable parts (typeId, rowPrefix, value) and applies fixed configuration.
 * 
 * @package Ksfraser\HTML\Cells
 */
class EditableTypeNameCell extends NameTableCell {
    /**
     * Build editable type name cell
     * 
     * @param mixed $value The name value
     * @param mixed $typeId The type ID
     * @param string $rowPrefix The row prefix for cell ID generation
     * @return TableData
     */
    public function buildEditable($value, $typeId, string $rowPrefix): TableData {
        $this->setId("name-cell-{$rowPrefix}")
            ->setAttribute('data-type-id', (string)$typeId)
            ->setAttribute('data-field', 'name')
            ->setDblClickHandler('editTypeCell(this)')
            ->setEditable(true);
        
        return parent::build($value);
    }
}
