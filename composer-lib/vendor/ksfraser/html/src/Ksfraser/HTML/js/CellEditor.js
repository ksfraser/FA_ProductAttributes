/**
 * Generic Cell Editor - Base class for spreadsheet-style cell editing
 * 
 * SRP: Single responsibility of managing cell edit state and UI.
 * Handles: Double-click activation, contenteditable toggling, save/cancel buttons, data capture.
 * Emits custom events for application-specific handling via handlers.
 * 
 * Usage:
 *   const editor = new CellEditor('cell-id', {
 *       onSave: (value) => console.log('Save:', value),
 *       onCancel: () => console.log('Cancelled')
 *   });
 *   editor.attach();
 */
class CellEditor {
    constructor(cellId, options = {}) {
        this.cell = document.getElementById(cellId);
        if (!this.cell) {
            console.warn(`CellEditor: Cell with id "${cellId}" not found`);
            return;
        }
        
        this.cellId = cellId;
        this.originalValue = this.cell.textContent.trim();
        this.isEditing = false;
        this.options = {
            onSave: options.onSave || (() => {}),
            onCancel: options.onCancel || (() => {}),
            beforeSave: options.beforeSave || ((value) => value),
            validator: options.validator || ((value) => true),
            ...options
        };
    }
    
    /**
     * Attach event listeners to the cell
     */
    attach() {
        if (!this.cell) return;
        
        // Double-click to edit
        this.cell.addEventListener('dblclick', () => this.startEdit());
        
        // Keyboard shortcuts
        this.cell.addEventListener('keydown', (e) => this.handleKeydown(e));
    }
    
    /**
     * Start editing the cell
     */
    startEdit() {
        if (this.isEditing) return;
        
        this.isEditing = true;
        this.originalValue = this.cell.textContent.trim();
        
        // Make cell editable
        this.cell.setAttribute('contenteditable', 'true');
        this.cell.classList.add('editing');
        this.cell.focus();
        
        // Select all text
        this.selectAllText();
        
        // Add save/cancel buttons
        this.addEditButtons();
    }
    
    /**
     * Select all text in the cell for easy replacement
     */
    selectAllText() {
        const range = document.createRange();
        const sel = window.getSelection();
        range.selectNodeContents(this.cell);
        sel.removeAllRanges();
        sel.addRange(range);
    }
    
    /**
     * Add save and cancel buttons for editing
     */
    addEditButtons() {
        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'cell-edit-buttons';
        buttonContainer.style.cssText = `
            margin-top: 4px;
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        `;
        
        // Save button
        const saveBtn = document.createElement('button');
        saveBtn.textContent = '✓ Save';
        saveBtn.className = 'cell-save-btn';
        saveBtn.style.cssText = `
            padding: 2px 8px;
            font-size: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        `;
        saveBtn.addEventListener('click', () => this.save());
        
        // Cancel button
        const cancelBtn = document.createElement('button');
        cancelBtn.textContent = '✕ Cancel';
        cancelBtn.className = 'cell-cancel-btn';
        cancelBtn.style.cssText = `
            padding: 2px 8px;
            font-size: 12px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        `;
        cancelBtn.addEventListener('click', () => this.cancel());
        
        // Append to cell (if cell has room) or insert after
        buttonContainer.appendChild(saveBtn);
        buttonContainer.appendChild(cancelBtn);
        
        this.cell.appendChild(document.createElement('br'));
        this.cell.appendChild(buttonContainer);
        this.buttonContainer = buttonContainer;
    }
    
    /**
     * Handle keyboard events during editing
     */
    handleKeydown(e) {
        if (!this.isEditing) return;
        
        if (e.key === 'Enter' && e.ctrlKey) {
            // Ctrl+Enter to save
            e.preventDefault();
            this.save();
        } else if (e.key === 'Escape') {
            // Escape to cancel
            e.preventDefault();
            this.cancel();
        }
    }
    
    /**
     * Save changes and exit edit mode
     */
    save() {
        if (!this.isEditing) return;
        
        let newValue = this.cell.textContent
            .replace(this.buttonContainer?.outerHTML || '', '')
            .trim();
        
        // Remove button container from cell content
        if (this.buttonContainer) {
            this.buttonContainer.remove();
        }
        
        // Validate
        if (!this.options.validator(newValue)) {
            alert('Invalid value. Edit cancelled.');
            this.cancel();
            return;
        }
        
        // Apply beforeSave transformation
        newValue = this.options.beforeSave(newValue);
        
        // Exit edit mode
        this.isEditing = false;
        this.cell.setAttribute('contenteditable', 'false');
        this.cell.classList.remove('editing');
        this.cell.textContent = newValue;
        
        // Trigger save callback
        this.options.onSave(newValue, this.cellId, this.originalValue);
    }
    
    /**
     * Cancel editing and restore original value
     */
    cancel() {
        if (!this.isEditing) return;
        
        // Remove button container
        if (this.buttonContainer) {
            this.buttonContainer.remove();
        }
        
        // Exit edit mode
        this.isEditing = false;
        this.cell.setAttribute('contenteditable', 'false');
        this.cell.classList.remove('editing');
        this.cell.textContent = this.originalValue;
        
        // Trigger cancel callback
        this.options.onCancel(this.cellId);
    }
    
    /**
     * Get the current value (edited or original)
     */
    getValue() {
        return this.cell.textContent.trim();
    }
    
    /**
     * Set a new value programmatically
     */
    setValue(value) {
        this.originalValue = value;
        this.cell.textContent = value;
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CellEditor;
}
