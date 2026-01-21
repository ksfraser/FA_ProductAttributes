<?php

namespace Ksfraser\HTML\CSS;

/**
 * CSSProviderInterface - Interface for injectable CSS providers
 * Allows dependency injection of different CSS styling approaches
 */
interface CSSProviderInterface {
    /**
     * Get CSS string for this provider
     */
    public function getCSS();
    
    /**
     * Get provider name/identifier
     */
    public function getName();
    
    /**
     * Check if provider supports current theme
     */
    public function supportsTheme($theme);
}
