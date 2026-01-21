<?php
namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\Button;

/**
 * DeleteTypeActionButton - Encapsulates delete button for loan type rows
 * 
 * SRP: Encapsulate the specific configuration for delete button in loan type tables.
 * Takes variable parts (typeId) and applies fixed configuration (onclick handler, styling).
 * 
 * @package Ksfraser\HTML\Buttons
 */
class DeleteTypeActionButton extends Button {
    /**
     * Build delete type action button
     * 
     * @param mixed $typeId The type ID
     * @return Button
     */
    public function build($typeId): Button {
        return $this
            ->setType('button')
            ->addClass('btn-small btn-delete')
            ->setText('Delete')
            ->setAttribute('onclick', 'window.loanTypeHandler && window.loanTypeHandler.delete(' . intval($typeId ?? 0) . ')');
    }
}
