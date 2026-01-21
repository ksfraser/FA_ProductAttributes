<?php
namespace Ksfraser\HTML;

use Ksfraser\HTML\Elements\Stylesheet;

/**
 * AbstractStylesheetManager - Generic stylesheet loading with caching and security
 * 
 * Provides reusable functionality for managing CSS asset loading across any module.
 * Handles stylesheet element rendering, caching for performance, and security.
 * 
 * Uses the Stylesheet element (extends HtmlElement) to generate properly formatted
 * <link rel="stylesheet"> tags via the HTML builder pattern.
 * 
 * Subclasses implement the specific stylesheet configuration via:
 * - $commonSheets: Array of common stylesheet names (cached, shared across views)
 * - $viewSheets: Associative array mapping view names to their specific stylesheets
 * 
 * Compatible with platform-specific asset resolution via asset_url() function:
 * - FrontAccounting: Searches user skin → module → default paths
 * - WordPress: Uses theme/plugin asset paths
 * - SuiteCRM: Uses theme structure
 * - Standalone: Simple path-based resolution
 * 
 * @package Ksfraser\HTML
 */
abstract class AbstractStylesheetManager {
    /**
     * Cache for common stylesheets (loaded once per request)
     * @var string|null
     */
    private static ?string $commonStylesheetsCache = null;
    
    /**
     * Common stylesheet names (shared across all views)
     * Subclasses override this with their specific sheets
     * @var array<string>
     */
    protected static array $commonSheets = [];
    
    /**
     * View-specific stylesheet names (unique per view)
     * Subclasses override this with their view mappings
     * @var array<string, array<string>>
     */
    protected static array $viewSheets = [];
    
    /**
     * Get all common stylesheets
     * 
     * Common stylesheets are cached to prevent duplicate loading across multiple views.
     * This is efficient for pages with multiple components of the same module.
     * 
     * @return string HTML rendering of common stylesheet link elements
     */
    final public static function getCommonStylesheets(): string {
        // Return cached result on subsequent calls
        if (self::$commonStylesheetsCache !== null) {
            return self::$commonStylesheetsCache;
        }
        
        if (!function_exists('asset_url')) {
            self::$commonStylesheetsCache = '';
            return '';
        }
        
        $links = '';
        foreach (static::$commonSheets as $sheet) {
            $links .= self::buildStylesheetLink($sheet);
        }
        
        self::$commonStylesheetsCache = $links;
        return $links;
    }
    
    /**
     * Get view-specific stylesheets
     * 
     * View-specific stylesheets are loaded separately to allow:
     * 1. Selective override per view in skins
     * 2. Loading only needed styles for specific views
     * 3. Clear separation of concerns (common vs unique styling)
     * 
     * @param string $viewName View identifier (e.g., 'loan-types', 'reporting')
     * @return string HTML rendering of view-specific stylesheet link elements
     */
    final public static function getViewStylesheets(string $viewName): string {
        if (!function_exists('asset_url')) {
            return '';
        }
        
        $viewName = strtolower(trim($viewName));
        $sheets = static::$viewSheets[$viewName] ?? [];
        
        $links = '';
        foreach ($sheets as $sheet) {
            $links .= self::buildStylesheetLink($sheet);
        }
        
        return $links;
    }
    
    /**
     * Get all stylesheets for a view (common + view-specific)
     * 
     * Convenience method for views that want one call to get everything.
     * 
     * @param string $viewName View identifier
     * @return string HTML rendering of all stylesheet link elements
     */
    final public static function getStylesheets(string $viewName): string {
        return self::getCommonStylesheets() . self::getViewStylesheets($viewName);
    }
    
    /**
     * Build a single stylesheet link element using Stylesheet class
     * 
     * Creates a Stylesheet element, configures it, and calls render() to generate HTML.
     * This maintains consistency with the HTML builder pattern used throughout codebase.
     * 
     * @param string $sheetName Stylesheet name (without .css extension)
     * @return string HTML rendering of <link rel="stylesheet"> element
     */
    final protected static function buildStylesheetLink(string $sheetName): string
    {
        $url = asset_url('css/' . $sheetName . '.css');
        
        // Create and configure Stylesheet element
        $stylesheet = (new Stylesheet())
            ->setRel('stylesheet')
            ->setHref($url);
        
        // Render element to HTML string with newline
        return $stylesheet->render() . PHP_EOL;
    }
    
    /**
     * Clear common stylesheet cache
     * 
     * Useful for testing or if stylesheets change during execution.
     * 
     * @return void
     */
    final public static function clearCache(): void {
        self::$commonStylesheetsCache = null;
    }
    
    /**
     * Get stylesheet loading info (for debugging/documentation)
     * 
     * @return array<string, mixed> Information about common and view-specific sheets
     */
    final public static function getInfo(): array {
        return [
            'common_sheets' => static::$commonSheets,
            'view_sheets' => static::$viewSheets,
            'common_cached' => self::$commonStylesheetsCache !== null,
        ];
    }
}
