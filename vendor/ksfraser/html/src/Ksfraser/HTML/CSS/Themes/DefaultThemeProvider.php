<?php

namespace Ksfraser\HTML\CSS\Themes;

use Ksfraser\HTML\CSS\CSSProviderInterface;

/**
 * DefaultThemeProvider - Default application theme CSS provider
 * Provides the standard look and feel for the application
 */
class DefaultThemeProvider implements CSSProviderInterface {
    
    public function getName() {
        return 'default';
    }
    
    public function supportsTheme($theme) {
        return $theme === 'default' || $theme === 'light';
    }
    
    public function getCSS() {
        return '
        /* Default Theme Enhancements */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Enhanced card styling */
        .management-section {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease;
        }
        
        .management-section:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        /* Enhanced button interactions */
        .btn {
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        ';
    }
}
