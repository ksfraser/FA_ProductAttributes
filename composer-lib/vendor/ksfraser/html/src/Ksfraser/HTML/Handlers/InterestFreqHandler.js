/**
 * InterestFreqHandler - Handles interest calculation frequency operations
 * 
 * SRP: Single responsibility of managing interest frequency CRUD operations:
 * edit, delete via AJAX calls to backend. Handles user confirmation and UI updates.
 * 
 * @package Ksfraser\HTML\Handlers
 */
class InterestFreqHandler extends BaseHandler {
    /**
     * Constructor
     * @param {string} baseUrl - Base API URL
     */
    constructor(baseUrl = '/api/interest-frequencies') {
        super(baseUrl);
        this.currentId = null;
    }

    /**
     * Edit interest frequency - Opens edit dialog/modal or navigates to form
     * @param {number} id - Frequency ID to edit
     */
    async edit(id) {
        if (this.isLoading) {
            console.warn('Edit already in progress');
            return;
        }

        try {
            this.currentId = id;
            
            // Fetch frequency data
            const data = await this._request('GET', `/${id}`);
            
            // Emit custom event for application to handle modal/form display
            const event = new CustomEvent('interestFreqEdit', {
                detail: { id, data },
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
            this._showSuccess(`Loaded frequency #${id} for editing`);
            
        } catch (error) {
            console.error('Edit failed:', error);
        }
    }

    /**
     * Delete interest frequency with user confirmation
     * @param {number} id - Frequency ID to delete
     */
    async delete(id) {
        if (this.isLoading) {
            console.warn('Delete already in progress');
            return;
        }

        if (!confirm('Delete this frequency? This action cannot be undone.')) {
            return;
        }

        try {
            await this._request('DELETE', `/${id}`);
            this._showSuccess('Frequency deleted successfully');
            
            // Emit custom event for UI refresh
            const event = new CustomEvent('interestFreqDeleted', {
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
     * Create new interest frequency
     * @param {object} frequencyData - {name, description}
     */
    async create(frequencyData) {
        if (this.isLoading) {
            console.warn('Create already in progress');
            return;
        }

        try {
            const result = await this._request('POST', '', frequencyData);
            this._showSuccess('Frequency created successfully');
            
            const event = new CustomEvent('interestFreqCreated', {
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
     * Update interest frequency
     * @param {number} id - Frequency ID
     * @param {object} frequencyData - {name, description}
     */
    async update(id, frequencyData) {
        if (this.isLoading) {
            console.warn('Update already in progress');
            return;
        }

        try {
            const result = await this._request('PUT', `/${id}`, frequencyData);
            this._showSuccess('Frequency updated successfully');
            
            const event = new CustomEvent('interestFreqUpdated', {
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
     * Get the endpoint for field updates on interest frequencies
     * @param {number} id - Frequency ID
     * @param {string} fieldName - Field being updated
     * @return {string} API endpoint
     * @protected
     */
    getUpdateFieldEndpoint(id, fieldName) {
        return `/${id}`;
    }
}

// Create global singleton instance
window.interestFreqHandler = new InterestFreqHandler();
