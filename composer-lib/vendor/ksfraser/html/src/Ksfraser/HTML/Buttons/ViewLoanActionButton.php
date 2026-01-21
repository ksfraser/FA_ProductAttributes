<?php
namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\Button;

/**
 * ViewLoanActionButton - Encapsulates view button for loan rows
 * 
 * SRP: Encapsulate the specific configuration for view button in loan summaries.
 * Takes variable parts (loanId) and applies fixed configuration (onclick handler, styling).
 * 
 * @package Ksfraser\HTML\Buttons
 */
class ViewLoanActionButton extends Button {
    /**
     * Build view loan action button
     * 
     * @param mixed $loanId The loan ID
     * @return Button
     */
    public function build($loanId): Button {
        return $this
            ->setType('button')
            ->addClass('btn-small btn-view')
            ->setText('View')
            ->setAttribute('onclick', 'window.loanHandler && window.loanHandler.view(' . intval($loanId ?? 0) . ')');
    }
}
