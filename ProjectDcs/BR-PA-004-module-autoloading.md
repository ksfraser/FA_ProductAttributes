# BR-PA-004 — Module Autoloading

## Status

Fixed

## Statement

The FA_ProductAttributes `hooks.php` shall load Composer dependencies using
the most reliable path available. It shall prefer the local
`vendor/autoload.php` and fall back to running `composer install` directly
when the vendor directory is missing.

## Rationale

The previous implementation attempted to load `ComposerDependencies` from
`ksf_FA_Common/src/`, which caused "Cannot redeclare class" fatals when the
same FQCN was already registered by the consuming module's PSR-4 autoloader.

## Acceptance Criteria

1. If `vendor/autoload.php` exists, load it and return.
2. If `composer.json` exists but `vendor/autoload.php` does not, run
   `composer install` directly via `exec()`.
3. No reference to `ksfraser\FrontAccounting\Common\Utils\ComposerDependencies`
   in the autoloader path.
