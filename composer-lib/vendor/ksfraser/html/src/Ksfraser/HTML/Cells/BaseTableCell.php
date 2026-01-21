<?php
namespace Ksfraser\HTML\Cells;

use Ksfraser\HTML\Elements\TableData;

/**
 * BaseTableCell - Abstract base for reusable table cells
 * 
 * SRP: Single responsibility of building specific cell types.
 * Each cell class handles formatting, styling, and content for its domain.
 * Supports fluent interface for optional attributes, IDs, and event handlers.
 * 
 * @package Ksfraser\HTML\Cells
 */
abstract class BaseTableCell {
    /**
     * @var string Optional cell ID for referencing
     */
    protected $id = '';
    
    /**
     * @var array Custom attributes (data-*, aria-*, etc)
     */
    protected $attributes = [];
    
    /**
     * @var string Optional ondblclick handler for spreadsheet editing
     */
    protected $dblClickHandler = '';
    
    /**
     * @var bool Whether cell is editable (contenteditable)
     */
    protected $isEditable = false;

    /**
     * Set cell ID for referencing
     * 
     * @param string $id HTML id attribute
     * @return self
     */
    public function setId(string $id): self {
        $this->id = $id;
        return $this;
    }

    /**
     * Set custom attribute (data-*, aria-*, etc)
     * 
     * @param string $name Attribute name
     * @param string $value Attribute value
     * @return self
     */
    public function setAttribute(string $name, string $value): self {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set ondblclick handler for spreadsheet-like editing
     * 
     * @param string $handler JavaScript function call (e.g., 'editCell(this)')
     * @return self
     */
    public function setDblClickHandler(string $handler): self {
        $this->dblClickHandler = $handler;
        return $this;
    }

    /**
     * Set cell as editable (contenteditable attribute)
     * 
     * @param bool $editable Whether cell should be contenteditable
     * @return self
     */
    public function setEditable(bool $editable = true): self {
        $this->isEditable = $editable;
        return $this;
    }

    /**
     * Build a table cell with appropriate content and styling
     * 
     * @param mixed $value The value to display in the cell
     * @return TableData
     */
    abstract public function build($value): TableData;

    /**
     * Apply configured attributes to a TableData element
     * 
     * @param TableData $cell The cell to configure
     * @return TableData
     * @protected
     */
    protected function applyAttributes(TableData $cell): TableData {
        if ($this->id) {
            $cell->setAttribute('id', $this->id);
        }
        
        foreach ($this->attributes as $name => $value) {
            $cell->setAttribute($name, $value);
        }
        
        if ($this->dblClickHandler) {
            $cell->setAttribute('ondblclick', $this->dblClickHandler);
        }
        
        if ($this->isEditable) {
            $cell->setAttribute('contenteditable', 'true');
        }
        
        return $cell;
    }
}
