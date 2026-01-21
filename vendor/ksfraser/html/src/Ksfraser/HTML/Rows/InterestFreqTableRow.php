<?php
namespace Ksfraser\HTML\Rows;

use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Cells\IdFrequencyTableCell;
use Ksfraser\HTML\Cells\EditableFrequencyNameCell;
use Ksfraser\HTML\Cells\EditableFrequencyDescriptionCell;
use Ksfraser\HTML\Buttons\EditFrequencyActionButton;
use Ksfraser\HTML\Buttons\DeleteFrequencyActionButton;

/**
 * InterestFreqTableRow - Builds interest frequency table rows
 * 
 * SRP: Single responsibility of building an interest frequency row from frequency data.
 * Handles: Row structure and action buttons. Uses cell classes for content cells.
 * 
 * @package Ksfraser\HTML\Rows
 */
class InterestFreqTableRow extends BaseTableRow {
    /**
     * Build interest frequency row
     * 
     * @param object $freq Frequency object with id, name, description
     * @return TableRow
     */
    public function build(object $freq): TableRow {
        $row = (new TableRow())->addClass('data-row');
        
        $freqId = $freq->id ?? null;
        $rowPrefix = $this->rowId ?: "freq-{$freqId}";
        
        // ID cell - configuration encapsulated in specific cell class
        $row->append((new IdFrequencyTableCell())->build($freqId, $rowPrefix));
        
        // Editable cells - configuration encapsulated in specific cell classes
        $row->append((new EditableFrequencyNameCell())->buildEditable($freq->name ?? null, $freqId, $rowPrefix));
        $row->append((new EditableFrequencyDescriptionCell())->buildEditable($freq->description ?? null, $freqId, $rowPrefix));
        
        // Actions cell
        $actionsCell = (new TableData())->addClass('actions-cell');
        $actionsDiv = (new Div())->addClass('action-buttons');
        
        // Action buttons - configuration encapsulated in specific button classes
        $actionsDiv->append((new EditFrequencyActionButton())->build($freqId));
        $actionsDiv->append((new DeleteFrequencyActionButton())->build($freqId));
        
        $actionsCell->append($actionsDiv);
        $row->append($actionsCell);
        
        return $row;
    }
}
