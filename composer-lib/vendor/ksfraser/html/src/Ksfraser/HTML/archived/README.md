# Archived HTML Classes

This directory contains legacy HTML class files that have been superseded by the reorganized structure under `Elements/`, `CSS/`, and `Composites/`.

## Why These Files Are Archived

These files were duplicate implementations that existed in the base `src/Ksfraser/HTML/` directory alongside the newer, organized implementations in subdirectories. They have been moved here to:

1. **Clean up the base directory** - Separate core infrastructure from element implementations
2. **Preserve history** - Keep files accessible rather than deleting them permanently
3. **Enable future migration** - Allow gradual transition for any code still using the old namespace
4. **Maintain backward compatibility** - Files remain accessible if needed

## Namespace Information

### Archived Files
- **Namespace**: `Ksfraser\HTML\HTMLAtomic` (for HTML elements)
- **Namespace**: `Ksfraser\HTML` (for VIEW_* classes)

### Current Files (Use These Instead)
- **HTML Elements**: `Ksfraser\HTML\Elements` namespace in `src/Ksfraser/HTML/Elements/`
- **CSS Classes**: `Ksfraser\HTML\CSS` namespace in `src/Ksfraser/HTML/CSS/`
- **Composites**: `Ksfraser\HTML\Composites` namespace in `src/Ksfraser/HTML/Composites/`

## File Mappings

### HTML Element Files (77 files)
All files in this directory with `Html*.php` naming have equivalent implementations in `../Elements/`:

| Archived File | Current Implementation | Notes |
|--------------|------------------------|-------|
| HtmlA.php | Elements/HtmlA.php | Link element |
| HtmlB.php | Elements/HtmlB.php | Bold element |
| HtmlOb | Elements/HtmlOB.php | Note: Casing standardized to OB |
| HtmlString | Elements/HtmlString.php | File had no extension |
| *(and 73 more)* | Elements/*.php | Same name in Elements |

### VIEW Composite Files (11 files)
Legacy VIEW classes have been reimplemented in Composites:

| Archived File | Current Implementation |
|--------------|------------------------|
| class.VIEW_TABLE.php | Composites/HTML_TABLE.php |
| class.VIEW_ROW.php | Composites/HTML_ROW.php |
| class.VIEW_CELL.php | *(archived, no direct replacement)* |
| class.VIEW_AMOUNT_CELL.php | *(archived)* |
| class.VIEW_DATE_CELL.php | *(archived)* |
| class.VIEW_SUBMIT_CELL.php | *(archived)* |
| class.VIEW_DIV.php | *(archived)* |
| class.VIEW_FORM.php | *(archived)* |
| class.VIEW_TABLE_TD.php | *(archived)* |
| class.VIEW_TABLE_TH.php | *(archived)* |

## Using Current Classes

### For New Code
Use PSR-4 autoloading with the proper namespaces:

```php
<?php
use Ksfraser\HTML\Elements\HtmlDiv;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Composites\HTML_TABLE;

$div = new HtmlDiv(new HtmlString("Content"));
$table = new HTML_TABLE();
```

### Composer Autoloading
The `composer.json` file includes PSR-4 autoload mappings:
- `Ksfraser\HTML\` → `src/Ksfraser/HTML/`
- `Ksfraser\HTML\Elements\` → `src/Ksfraser/HTML/Elements/`
- `Ksfraser\HTML\CSS\` → `src/Ksfraser/HTML/CSS/`
- `Ksfraser\HTML\Composites\` → `src/Ksfraser/HTML/Composites/`

Run `composer dump-autoload` after updating composer.json.

## Backward Compatibility

If you have code that still references the old `HTMLAtomic` namespace classes, you can:

1. **Option 1**: Update to use `Ksfraser\HTML\Elements` namespace (recommended)
2. **Option 2**: Temporarily require the archived files explicitly until migration is complete
3. **Option 3**: Create class aliases if needed for gradual migration

## Testing

Legacy tests for archived VIEW classes are maintained in `tests/` with a bootstrap file (`tests/bootstrap.php`) that loads the archived classes as needed.

## Important Notes

- **No API Changes**: Class names and public APIs remain the same; only namespaces and file locations changed
- **Casing Differences**: Some files had casing standardized (e.g., `HtmlOb` → `HtmlOB.php`)
- **Git History Preserved**: Files were moved with `git mv` to maintain full history
- **Not for New Code**: These archived files should not be used in new code
- **Pre-existing Issues**: Some archived files may have syntax errors or incomplete implementations (e.g., class.VIEW.php)

## Migration Date

Files archived: 2024-10-31 (Commit: Archive duplicate files and add PSR-4 autoloading)

## Questions?

For questions about migrating from archived to current classes, consult the documentation in:
- `src/Ksfraser/HTML/Elements/` - For HTML element classes
- `src/Ksfraser/HTML/Composites/` - For composite/wrapper classes
- `src/Ksfraser/HTML/CSS/` - For CSS management classes
