<?php
namespace Ksfraser\HTML\ScriptHandlers;

use Ksfraser\HTML\Elements\HtmlScript;

/**
 * LoanTypeScriptHandler - Script setup for loan type operations
 * 
 * SRP: Single responsibility of building loan type handler scripts.
 * Returns handler class loader and event listener setup.
 * 
 * @package Ksfraser\HTML\ScriptHandlers
 */
class LoanTypeScriptHandler extends BaseScriptHandler {
    /**
     * Build handler scripts
     * 
     * @return HtmlScript[]
     */
    public function build(): array {
        return [
            // Load handler classes
            (new HtmlScript())->setAttribute('src', '/js/handlers/BaseHandler.js'),
            (new HtmlScript())->setAttribute('src', '/js/handlers/LoanTypeHandler.js'),
            
            // Setup event listeners
            (new HtmlScript())->setContent($this->getEventListeners())
        ];
    }

    /**
     * Get inline event listener code
     * 
     * @return string
     * @private
     */
    private function getEventListeners(): string {
        return <<<'JS'
// Event listeners for loan type handler responses
document.addEventListener('loanTypeEdit', (e) => {
    console.log('Loan type edit event:', e.detail);
    // TODO: Open edit form with loan type data
    // window.showEditLoanTypeForm(e.detail.data);
});

document.addEventListener('loanTypeDeleted', (e) => {
    console.log('Loan type deleted event:', e.detail);
    // TODO: Reload table or remove row from DOM
    // window.location.reload();
});

document.addEventListener('loanTypeCreated', (e) => {
    console.log('Loan type created event:', e.detail);
    // TODO: Reload table or add new row to DOM
    // window.location.reload();
});

document.addEventListener('loanTypeUpdated', (e) => {
    console.log('Loan type updated event:', e.detail);
    // TODO: Update table row with new data
    // window.location.reload();
});
JS;
    }
}
