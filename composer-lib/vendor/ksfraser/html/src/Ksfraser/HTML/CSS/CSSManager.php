<?php

namespace Ksfraser\HTML\CSS;

/**
 * CSSManager - Centralized CSS management with dependency injection support
 * Supports themes/skins and provides injectable CSS providers
 */
class CSSManager {
    private static $currentTheme = 'default';
    private static $cssCache = [];
    private static $cssProviders = [];
    
    /**
     * Register a CSS provider for dependency injection
     */
    public static function registerProvider($name, $provider) {
        self::$cssProviders[$name] = $provider;
    }
    
    /**
     * Get a registered CSS provider
     */
    public static function getProvider($name) {
        return self::$cssProviders[$name] ?? null;
    }
    
    /**
     * Set the current theme
     */
    public static function setTheme($theme = 'default') {
        self::$currentTheme = $theme;
        self::$cssCache = []; // Clear cache when theme changes
    }
    
    /**
     * Get the current theme
     */
    public static function getCurrentTheme() {
        return self::$currentTheme;
    }
    
    /**
     * Get all CSS for the application (includes NavigationManager CSS)
     */
    public static function getAllCSS() {
        $cacheKey = 'all_css_' . self::$currentTheme;
        
        if (isset(self::$cssCache[$cacheKey])) {
            return self::$cssCache[$cacheKey];
        }
        
        $css = '';
        
        // Include NavigationManager CSS for compatibility
        if (class_exists('NavigationManager')) {
            $navManager = new \NavigationManager();
            $css .= $navManager->getNavigationCSS();
        }
        
        // Add our centralized CSS
        $css .= self::getBaseCSS();
        $css .= self::getFormCSS();
        $css .= self::getTableCSS();
        $css .= self::getCardCSS();
        $css .= self::getUtilityCSS();
        $css .= self::getThemeCSS();
        
        // Add any registered provider CSS
        foreach (self::$cssProviders as $provider) {
            if (method_exists($provider, 'getCSS')) {
                $css .= $provider->getCSS();
            }
        }
        
        self::$cssCache[$cacheKey] = $css;
        return $css;
    }
    
    /**
     * Get base/reset CSS
     */
    public static function getBaseCSS() {
        return '
        /* Base Application CSS */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        ';
    }
    
    /**
     * Get navigation CSS
     */
    public static function getNavigationCSS() {
        return '
        /* Navigation CSS */
        .nav-header {
            background: linear-gradient(135deg, #007cba, #005a87);
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .nav-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .quick-access {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .quick-access a {
            margin-right: 10px;
            color: #007cba;
            text-decoration: none;
        }
        
        .quick-access a:hover {
            text-decoration: underline;
        }
        ';
    }
    
    /**
     * Get form CSS
     */
    public static function getFormCSS() {
        return '
        /* Form CSS */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2);
        }
        ';
    }
    
    /**
     * Get table CSS
     */
    public static function getTableCSS() {
        return '
        /* Table CSS */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .user-table th,
        .user-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .user-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .user-table tr:hover {
            background-color: #f5f5f5;
        }
        ';
    }
    
    /**
     * Get card/section CSS
     */
    public static function getCardCSS() {
        return '
        /* Card/Section CSS */
        .management-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007cba;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        ';
    }
    
    /**
     * Get utility CSS (buttons, alerts, etc.)
     */
    public static function getUtilityCSS() {
        return '
        /* Utility CSS */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a87;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        ';
    }
    
    /**
     * Get theme-specific CSS
     */
    public static function getThemeCSS() {
        switch (self::$currentTheme) {
            case 'dark':
                return self::getDarkThemeCSS();
            case 'light':
                return self::getLightThemeCSS();
            default:
                return ''; // Default theme uses base colors
        }
    }
    
    /**
     * Get dark theme CSS
     */
    private static function getDarkThemeCSS() {
        return '
        /* Dark Theme */
        body {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        
        .management-section {
            background: #2d2d2d;
            color: #e0e0e0;
        }
        
        .user-table th {
            background-color: #404040;
        }
        
        .user-table tr:hover {
            background-color: #404040;
        }
        ';
    }
    
    /**
     * Get light theme CSS
     */
    private static function getLightThemeCSS() {
        return '
        /* Light Theme (Enhanced) */
        body {
            background-color: #ffffff;
        }
        
        .management-section {
            background: #fafafa;
            border: 1px solid #e0e0e0;
        }
        ';
    }
    
    /**
     * Render CSS in a style tag
     */
    public static function renderCSS() {
        echo '<style>' . self::getAllCSS() . '</style>';
    }
    
    /**
     * Get CSS as a string (for inline use)
     */
    public static function getCSS() {
        return self::getAllCSS();
    }
}
