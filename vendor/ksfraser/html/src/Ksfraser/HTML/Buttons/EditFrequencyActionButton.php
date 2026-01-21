<?php
namespace Ksfraser\HTML\Buttons;

use Ksfraser\HTML\Elements\Button;

/**
 * EditFrequencyActionButton - Encapsulates edit button for frequency rows
 * 
 * SRP: Encapsulate the specific configuration for edit button in frequency tables.
 * Takes variable parts (freqId) and applies fixed configuration (onclick handler, styling).
 * 
 * @package Ksfraser\HTML\Buttons
 */
class EditFrequencyActionButton extends Button {
    /**
     * Build edit frequency action button
     * 
     * @param mixed $freqId The frequency ID
     * @return Button
     */
    public function build($freqId): Button {
        return $this
            ->setType('button')
            ->addClass('btn-small btn-edit')
            ->setText('Edit')
            ->setAttribute('onclick', 'window.interestFreqHandler && window.interestFreqHandler.edit(' . intval($freqId ?? 0) . ')');
    }
}
