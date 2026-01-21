<?php
namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\Button;

/**
 * EditTypeActionButton - Encapsulates edit button for loan type rows
 * 
 * SRP: Encapsulate the specific configuration for edit button in loan type tables.
 * Takes variable parts (typeId) and applies fixed configuration (onclick handler, styling).
 * 
 * @package Ksfraser\HTML\Buttons
 */
class EditTypeActionButton extends Button {
    /**
     * Build edit type action button
     * 
     * @param mixed $typeId The type ID
     * @return Button
     */
    public function build($typeId): Button {
        return $this
            ->setType('button')
            ->addClass('btn-small btn-edit')
            ->setText('Edit')
            ->setAttribute('onclick', 'window.loanTypeHandler && window.loanTypeHandler.edit(' . intval($typeId ?? 0) . ')');
    }
}
