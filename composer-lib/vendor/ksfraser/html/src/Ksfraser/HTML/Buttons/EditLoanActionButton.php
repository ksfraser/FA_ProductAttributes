<?php
namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\Button;

/**
 * EditLoanActionButton - Encapsulates edit button for loan rows
 * 
 * SRP: Encapsulate the specific configuration for edit button in loan summaries.
 * Takes variable parts (loanId) and applies fixed configuration (onclick handler, styling).
 * 
 * @package Ksfraser\HTML\Buttons
 */
class EditLoanActionButton extends Button {
    /**
     * Build edit loan action button
     * 
     * @param mixed $loanId The loan ID
     * @return Button
     */
    public function build($loanId): Button {
        return $this
            ->setType('button')
            ->addClass('btn-small btn-edit')
            ->setText('Edit')
            ->setAttribute('onclick', 'window.loanHandler && window.loanHandler.edit(' . intval($loanId ?? 0) . ')');
    }
}
