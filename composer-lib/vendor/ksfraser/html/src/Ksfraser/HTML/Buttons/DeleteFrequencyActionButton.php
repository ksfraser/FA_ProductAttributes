<?php
namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\Button;

/**
 * DeleteFrequencyActionButton - Encapsulates delete button for frequency rows
 * 
 * SRP: Encapsulate the specific configuration for delete button in frequency tables.
 * Takes variable parts (freqId) and applies fixed configuration (onclick handler, styling).
 * 
 * @package Ksfraser\HTML\Buttons
 */
class DeleteFrequencyActionButton extends Button {
    /**
     * Build delete frequency action button
     * 
     * @param mixed $freqId The frequency ID
     * @return Button
     */
    public function build($freqId): Button {
        return $this
            ->setType('button')
            ->addClass('btn-small btn-delete')
            ->setText('Delete')
            ->setAttribute('onclick', 'window.interestFreqHandler && window.interestFreqHandler.delete(' . intval($freqId ?? 0) . ')');
    }
}
