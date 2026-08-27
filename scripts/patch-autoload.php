<?php
/**
 * Post-autoload-dump script: removes ksf-fa-common PSR-4 entries from
 * Composer's generated autoload files in the FA container only.
 *
 * In the container, the deployed ksf_FA_Common module provides these classes.
 * Our vendor copy creates a namespace collision (same FQCN declared twice)
 * which fatals on PHP 7.x. Locally, the vendor copy is needed for tests.
 *
 * @package FA_ProductAttributes
 */

// Only patch inside the FA container (session.inc present).
if (!is_file('/var/www/html/includes/session.inc')) {
    echo "Local dev — skipping autoload patch.\n";
    return;
}

$dir = dirname(__DIR__) . '/vendor/composer';

foreach (['autoload_psr4.php', 'autoload_static.php'] as $file) {
    $path = $dir . '/' . $file;
    if (!is_file($path)) continue;

    $orig = file_get_contents($path);
    $content = $orig;

    // Split into lines, drop any line referencing ksf-fa-common
    $lines = explode("\n", $content);
    $out = [];
    $skip = 0; // lines to skip (for multi-line blocks)

    foreach ($lines as $i => $line) {
        if ($skip > 0) { $skip--; continue; }

        // Drop single-line references to ksf-fa-common
        if (strpos($line, 'ksf-fa-common') !== false) continue;

        // Drop 'k' => array (empty block left after removing ksfraser\FrontAccounting\Common)
        if (preg_match("/^\\s*'k'\\s*=>\\s*$/", $line)) {
            // Look ahead for "array (" then ")" — skip the whole block
            $j = $i + 1;
            while ($j < count($lines) && trim($lines[$j]) === '') $j++;
            if ($j < count($lines) && trim($lines[$j]) === 'array (') {
                $k = $j + 1;
                while ($k < count($lines) && trim($lines[$k]) === '') $k++;
                if ($k < count($lines) && trim($lines[$k]) === '),') {
                    $skip = $k - $i; // skip from current to closing ),  inclusive
                    continue;
                }
            }
        }

        $out[] = $line;
    }

    $new = implode("\n", $out);
    if ($new !== $orig) {
        file_put_contents($path, $new);
        echo "Patched: $file\n";
    } else {
        echo "Already clean: $file\n";
    }
}
