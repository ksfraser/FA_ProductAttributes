/**
 * LoanHandler - Handles loan operations
 * 
 * SRP: Single responsibility of managing loan CRUD operations:
 * view, edit via AJAX calls to backend. Handles user actions and state management.
 * 
 * @package Ksfraser\HTML\Handlers
 */
class LoanHandler extends BaseHandler {
    /**
     * Constructor
     * @param {string} baseUrl - Base API URL
     */
    constructor(baseUrl = '/api/loans') {
        super(baseUrl);
        this.currentId = null;
        this.cache = new Map();
    }

    /**
     * View loan details - Fetch and display loan information
     * @param {number} id - Loan ID to view
     */
    async view(id) {
        if (this.isLoading) {
            console.warn('View already in progress');
            return;
        }

        try {
            this.currentId = id;
            
            // Check cache first
            if (this.cache.has(id)) {
                const cachedData = this.cache.get(id);
                console.log(`Using cached loan data for #${id}`);
                this._emitViewEvent(id, cachedData);
                return cachedData;
            }
            
            // Fetch from API
            const data = await this._request('GET', `/${id}`);
            this.cache.set(id, data);
            
            this._emitViewEvent(id, data);
            this._showSuccess(`Loan #${id} details loaded`);
            
            return data;
            
        } catch (error) {
            console.error('View failed:', error);
        }
    }

    /**
     * Edit loan - Open edit form/modal
     * @param {number} id - Loan ID to edit
     */
    async edit(id) {
        if (this.isLoading) {
            console.warn('Edit already in progress');
            return;
        }

        try {
            this.currentId = id;
            
            const data = await this._request('GET', `/${id}`);
            
            // Clear cache to get fresh data on save
            this.cache.delete(id);
            
            const event = new CustomEvent('loanEdit', {
                detail: { id, data },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            this._showSuccess(`Loan #${id} opened for editing`);
            
            return data;
            
        } catch (error) {
            console.error('Edit failed:', error);
        }
    }

    /**
     * Update loan
     * @param {number} id - Loan ID
     * @param {object} loanData - Loan update data
     */
    async update(id, loanData) {
        if (this.isLoading) {
            console.warn('Update already in progress');
            return;
        }

        try {
            const result = await this._request('PUT', `/${id}`, loanData);
            
            // Invalidate cache
            this.cache.delete(id);
            
            this._showSuccess('Loan updated successfully');
            
            const event = new CustomEvent('loanUpdated', {
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
     * Clear cache for a specific loan or all loans
     * @param {number} id - Optional: Loan ID to clear, or all if omitted
     */
    clearCache(id = null) {
        if (id) {
            this.cache.delete(id);
            console.log(`Cache cleared for loan #${id}`);
        } else {
            this.cache.clear();
            console.log('All loan cache cleared');
        }
    }

    /**
     * Emit view event to application
     * @private
     */
    _emitViewEvent(id, data) {
        const event = new CustomEvent('loanViewed', {
            detail: { id, data },
            bubbles: true,
            cancelable: true
        });
        
        document.dispatchEvent(event);
    }

    /**
     * Get the endpoint for field updates on loans
     * @param {number} id - Loan ID
     * @param {string} fieldName - Field being updated
     * @return {string} API endpoint
     * @protected
     */
    getUpdateFieldEndpoint(id, fieldName) {
        return `/${id}`;
    }
}

// Create global singleton instance
window.loanHandler = new LoanHandler();
