# Ksfraser\HTML\CSS - Centralized CSS Management System

A flexible, theme-aware CSS management system with dependency injection support.

## Features

- **Centralized CSS Management** - Single point of control for all application CSS
- **Theme Support** - Easy theme switching (default, dark, light, custom)
- **Dependency Injection** - Injectable CSS providers for extensibility
- **Performance** - CSS caching for optimal performance
- **Modular** - Individual CSS components can be used separately
- **Legacy Compatible** - Works with existing NavigationManager and other systems

## Installation

When packaged as a composer module:

```bash
composer require ksfraser/html-css
```

## Basic Usage

```php
use Ksfraser\HTML\CSS\CSSManager;

// Get all CSS for your application
echo '<style>' . CSSManager::getAllCSS() . '</style>';

// Or render directly
CSSManager::renderCSS();
```

## Theme Management

```php
// Set theme
CSSManager::setTheme('dark');

// Get current theme
$theme = CSSManager::getCurrentTheme();

// Theme-aware CSS generation
echo CSSManager::getAllCSS(); // Returns dark theme CSS
```

## Custom Providers

Create custom CSS providers by implementing `CSSProviderInterface`:

```php
use Ksfraser\HTML\CSS\CSSProviderInterface;

class MyCustomProvider implements CSSProviderInterface {
    public function getName() {
        return 'my-custom';
    }
    
    public function supportsTheme($theme) {
        return in_array($theme, ['default', 'custom']);
    }
    
    public function getCSS() {
        return '
        .my-custom-class {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
        }
        ';
    }
}

// Register the provider
CSSManager::registerProvider('custom', new MyCustomProvider());
```

## Available CSS Components

- `getBaseCSS()` - Base reset and typography
- `getFormCSS()` - Form styling (inputs, labels, validation)
- `getTableCSS()` - Table styling (borders, hover effects)
- `getCardCSS()` - Card/section styling (shadows, spacing)
- `getUtilityCSS()` - Utility classes (buttons, alerts, status)

## Architecture

```
Ksfraser\HTML\CSS\
├── CSSManager.php              # Main CSS coordinator
├── CSSProviderInterface.php    # Interface for injectable providers
├── Themes/
│   └── DefaultThemeProvider.php # Default theme implementation
└── Examples/
    └── test_css_manager.php    # Example usage and tests
```

## Testing

Run the example/test file to verify functionality:

```bash
php src/Ksfraser/HTML/CSS/Examples/test_css_manager.php
```

## Future Enhancements

- CSS minification for production
- SCSS/SASS compilation support
- CSS purging for unused styles
- Advanced theme inheritance
- Component-specific CSS injection
- Runtime CSS variable support

## License

MIT License - see LICENSE file for details.
