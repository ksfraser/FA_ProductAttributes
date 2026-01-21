<?php
namespace Ksfraser\HTML\Rows;

use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Cells\IdTypeTableCell;
use Ksfraser\HTML\Cells\EditableTypeNameCell;
use Ksfraser\HTML\Cells\EditableTypeDescriptionCell;
use Ksfraser\HTML\Buttons\EditTypeActionButton;
use Ksfraser\HTML\Buttons\DeleteTypeActionButton;

/**
 * LoanTypeTableRow - Builds loan type table rows
 * 
 * SRP: Single responsibility of building a loan type row from loan type data.
 * Handles: Row structure and action buttons. Uses cell classes for content cells.
 * 
 * @package Ksfraser\HTML\Rows
 */
class LoanTypeTableRow extends BaseTableRow {
    /**
     * Build loan type row
     * 
     * @param object $type Loan type object with id, name, description
     * @return TableRow
     */
    public function build(object $type): TableRow {
        $row = (new TableRow())->addClass('data-row');
        
        $typeId = $type->id ?? null;
        $rowPrefix = $this->rowId ?: "type-{$typeId}";
        
        // ID cell - configuration encapsulated in specific cell class
        $row->append((new IdTypeTableCell())->build($typeId, $rowPrefix));
        
        // Editable cells - configuration encapsulated in specific cell classes
        $row->append((new EditableTypeNameCell())->buildEditable($type->name ?? null, $typeId, $rowPrefix));
        $row->append((new EditableTypeDescriptionCell())->buildEditable($type->description ?? null, $typeId, $rowPrefix));
        
        // Actions cell
        $actionsCell = (new TableData())->addClass('actions-cell');
        $actionsDiv = (new Div())->addClass('action-buttons');
        
        // Action buttons - configuration encapsulated in specific button classes
        $actionsDiv->append((new EditTypeActionButton())->build($typeId));
        $actionsDiv->append((new DeleteTypeActionButton())->build($typeId));
        
        $actionsCell->append($actionsDiv);
        $row->append($actionsCell);
        
        return $row;
    }
}
