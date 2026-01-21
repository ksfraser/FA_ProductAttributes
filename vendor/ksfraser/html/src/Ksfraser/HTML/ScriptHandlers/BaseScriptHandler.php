<?php
namespace Ksfraser\HTML\ScriptHandlers;

use Ksfraser\HTML\Elements\HtmlScript;

/**
 * BaseScriptHandler - Abstract base for domain-specific script builders
 * 
 * SRP: Single responsibility of building domain-specific script setup.
 * Each handler builds script tags and event listeners for a domain.
 * 
 * @package Ksfraser\HTML\ScriptHandlers
 */
abstract class BaseScriptHandler {
    /**
     * Build handler script tags
     * 
     * Returns HtmlScript instances that setup handler classes and event listeners.
     * 
     * @return HtmlScript[]
     */
    abstract public function build(): array;

    /**
     * Render all scripts as HTML string
     * 
     * @return string
     */
    public function render(): string {
        $html = '';
        foreach ($this->build() as $script) {
            $html .= $script->getHtml() . "\n";
        }
        return $html;
    }

    /**
     * Magic method for rendering
     */
    public function __toString(): string {
        return $this->render();
    }
}
