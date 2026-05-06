<?php

namespace Ksfraser\FA_ProductAttributes\Test\Compatibility;

use PHPUnit\Framework\TestCase;

/**
 * NFR6 — Compatibility: verify the codebase is compatible with PHP 7.3 and
 * does not use constructs that were added in PHP 8.0+.
 *
 * Tests inspect the source files for known PHP 8.0+ syntax that would break
 * on the target environment (PHP 7.3, FrontAccounting 2.3.22).
 */
class CompatibilityTest extends TestCase
{
    /** @var string[] Source directories to scan */
    private static $sourceDirs = [
        'src',
        'fa_product_attributes_variations/src',
    ];

    /** @var string[] PHP 8.0+ patterns that must not appear in PHP source files */
    private static $php80Patterns = [
        // PHP 8 match expression — preceded by = or return, not a JS/method .match()
        '/(?<![.\w])match\s*\(\s*\$/u'            => 'match expression (PHP 8.0+)',
        // Nullsafe operator
        '/\?\->/u'                                 => 'Nullsafe operator ?-> (PHP 8.0+)',
        // Named arguments — only catch the explicit call-site form: func(name: $value)
        // Requires ( or , immediately before the identifier
        '/[(,]\s*[a-z_]\w*\s*:\s*\$[a-z_]/u'    => 'Named argument in function call (PHP 8.0+)',
        // Attributes #[...]
        '/#\[/u'                                   => 'PHP Attributes #[...] (PHP 8.0+)',
        // str_contains / str_starts_with / str_ends_with (PHP 8.0 builtins)
        '/\bstr_contains\s*\(/u'                   => 'str_contains() (PHP 8.0+, use strpos instead)',
        '/\bstr_starts_with\s*\(/u'                => 'str_starts_with() (PHP 8.0+)',
        '/\bstr_ends_with\s*\(/u'                  => 'str_ends_with() (PHP 8.0+)',
    ];

    /**
     * Collect all PHP source files from the configured source directories.
     *
     * @return array<int, string>
     */
    private function collectSourceFiles(): array
    {
        $root  = dirname(__DIR__, 2);
        $files = [];

        foreach (self::$sourceDirs as $relDir) {
            $dir = $root . DIRECTORY_SEPARATOR . $relDir;
            if (!is_dir($dir)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Verify we are running on PHP >= 7.3 (minimum supported version).
     */
    public function testPhpVersionIsAtLeast73(): void
    {
        $this->assertGreaterThanOrEqual(
            70300,
            PHP_VERSION_ID,
            'PHP 7.3+ is required (NFR6)'
        );
    }

    /**
     * Verify no PHP 8.0+ syntax appears in any source file.
     */
    public function testNoPhp80SyntaxInSourceFiles(): void
    {
        $files = $this->collectSourceFiles();

        if (empty($files)) {
            $this->markTestSkipped('No source files found to scan');
        }

        $violations = [];

        foreach ($files as $filePath) {
            $contents     = file_get_contents($filePath);
            $relativePath = str_replace(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, '', $filePath);

            foreach (self::$php80Patterns as $pattern => $description) {
                if (preg_match($pattern, $contents)) {
                    $violations[] = "{$relativePath}: {$description}";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "PHP 8.0+ syntax found — incompatible with PHP 7.3 target (NFR6):\n" . implode("\n", $violations)
        );
    }

    /**
     * Verify that every class uses compatible type hints (no PHP 8.0+ types).
     * Specifically: no `mixed`, `never`, `fibers`, `intersection types`.
     */
    public function testNoPhp80TypesUsed(): void
    {
        $files      = $this->collectSourceFiles();
        $violations = [];

        $php80Types = [
            '/:\s*mixed\b/u'                           => 'Type "mixed" as return/param type (PHP 8.0+)',
            '/:\s*never\b/u'                           => 'Type "never" return type (PHP 8.1+)',
            '/\w+\s*&\s*\w+\s*\$/u'                   => 'Intersection types (PHP 8.1+)',
            '/\benum\s+\w+/u'                          => 'Enums (PHP 8.1+)',
            '/readonly\s+(?:public|protected|private)/u' => 'readonly properties (PHP 8.1+)',
        ];

        foreach ($files as $filePath) {
            $contents     = file_get_contents($filePath);
            $relativePath = str_replace(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, '', $filePath);

            foreach ($php80Types as $pattern => $description) {
                if (preg_match($pattern, $contents)) {
                    $violations[] = "{$relativePath}: {$description}";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "PHP 8.0+/8.1+ types found — incompatible with PHP 7.3 (NFR6):\n" . implode("\n", $violations)
        );
    }

    /**
     * Verify all SQL uses prepared statements (no raw variable interpolation
     * directly into query strings — guards against injection AND FA DB layer
     * requirement for PHP 7.3 PDO compatibility).
     */
    public function testSqlUsesPreparedStatements(): void
    {
        $files      = $this->collectSourceFiles();
        $violations = [];

        foreach ($files as $filePath) {
            $contents     = file_get_contents($filePath);
            $relativePath = str_replace(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, '', $filePath);

            // Look for raw variable interpolation inside SQL strings.
            // We allow {$p} (table prefix) and $this, but flag other
            // multi-character variable names concatenated into SQL.
            // Restrict to single line to avoid spanning comments/docblocks.
            if (preg_match('/(?:SELECT|INSERT|UPDATE|DELETE)[^\n;"\']*\$(?!this|p[}\b])[a-zA-Z_]{2,}/u', $contents)) {
                $violations[] = $relativePath . ': possible raw variable in SQL (use :named params)';
            }
        }

        $this->assertEmpty(
            $violations,
            "Raw variable interpolation in SQL found (use :named params for PDO compatibility):\n"
            . implode("\n", $violations)
        );
    }
}
