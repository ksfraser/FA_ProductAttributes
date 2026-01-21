/**
 * LoanTypeHandler - Handles loan type operations
 * 
 * SRP: Single responsibility of managing loan type CRUD operations:
 * edit, delete, create via AJAX calls to backend. Handles state and user actions.
 * 
 * @package Ksfraser\HTML\Handlers
 */
class LoanTypeHandler extends BaseHandler {
    /**
     * Constructor
     * @param {string} baseUrl - Base API URL
     */
    constructor(baseUrl = '/api/loan-types') {
        super(baseUrl);
        this.currentId = null;
    }

    /**
     * Edit loan type - Open edit form/modal
     * @param {number} id - Loan type ID to edit
     */
    async edit(id) {
        if (this.isLoading) {
            console.warn('Edit already in progress');
            return;
        }

        try {
            this.currentId = id;
            
            const data = await this._request('GET', `/${id}`);
            
            const event = new CustomEvent('loanTypeEdit', {
                detail: { id, data },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            this._showSuccess(`Loan type #${id} opened for editing`);
            
            return data;
            
        } catch (error) {
            console.error('Edit failed:', error);
        }
    }

    /**
     * Delete loan type with user confirmation
     * @param {number} id - Loan type ID to delete
     */
    async delete(id) {
        if (this.isLoading) {
            console.warn('Delete already in progress');
            return;
        }

        if (!confirm('Delete this loan type? This action cannot be undone.')) {
            return;
        }

        try {
            await this._request('DELETE', `/${id}`);
            this._showSuccess('Loan type deleted successfully');
            
            const event = new CustomEvent('loanTypeDeleted', {
                detail: { id },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            
        } catch (error) {
            console.error('Delete failed:', error);
        }
    }

    /**
     * Create new loan type
     * @param {object} typeData - {name, description}
     */
    async create(typeData) {
        if (this.isLoading) {
            console.warn('Create already in progress');
            return;
        }

        try {
            const result = await this._request('POST', '', typeData);
            this._showSuccess('Loan type created successfully');
            
            const event = new CustomEvent('loanTypeCreated', {
                detail: { data: result },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            return result;
            
        } catch (error) {
            console.error('Create failed:', error);
        }
    }

    /**
     * Update loan type
     * @param {number} id - Loan type ID
     * @param {object} typeData - {name, description}
     */
    async update(id, typeData) {
        if (this.isLoading) {
            console.warn('Update already in progress');
            return;
        }

        try {
            const result = await this._request('PUT', `/${id}`, typeData);
            this._showSuccess('Loan type updated successfully');
            
            const event = new CustomEvent('loanTypeUpdated', {
                detail: { id, data: result },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            return result;
            
        } catch (error) {
            console.error('Update failed:', error);
        }
    }

    /**
     * Get the endpoint for field updates on loan types
     * @param {number} id - Loan type ID
     * @param {string} fieldName - Field being updated
     * @return {string} API endpoint
     * @protected
     */
    getUpdateFieldEndpoint(id, fieldName) {
        return `/${id}`;
    }
}

// Create global singleton instance
window.loanTypeHandler = new LoanTypeHandler();
