<?php

namespace Ksfraser\FA_ProductAttributes\Install;

/**
 * Single Responsibility: Ensure FA core `inventory/manage/items.php` carries the
 * 4 host hook points the module's tab system invokes (`item_display_tab_headers`,
 * `item_display_tab_content`, `post_item_write`, `pre_item_delete`).
 *
 * FA 2.4.3 core has no extension points on the items page (tabs are a hardcoded
 * array + switch), so the FIRST activation inserts the hook calls. Idempotency:
 * a `// KSF host hook` sentinel marks an already-patched file; re-runs are
 * no-ops. Each hunk is applied ONLY when its exact pristine anchor occurs exactly
 * once; ambiguity/absence fails that hunk softly instead of corrupting the file.
 * Mirrors `patches/items.php.ksf-tabs.patch` (keep both in sync).
 *
 * NOTE: replacements() anchors MUST use double-quoted strings — single quotes
 * leave `\t`/`\n`/`\$` as literal backslash sequences and never byte-match.
 */
class ItemsPhpTabHookPatcher
{
    /** Markers used by both installer and the ops patch. */
    const SENTINEL = '// KSF host hook';

    /** @var string */
    private $targetPath;

    /**
     * @param string|null $targetPath Absolute path to the FA inventory/manage/items.php.
     *                                null => auto-detect via $GLOBALS['path_to_root'],
     *                                falling back to deriving the FA root from this file.
     */
    public function __construct(?string $targetPath = null)
    {
        if ($targetPath !== null) {
            $this->targetPath = $targetPath;
            return;
        }

        $root = '';
        if (isset($GLOBALS['path_to_root']) && is_string($GLOBALS['path_to_root'])) {
            $root = $GLOBALS['path_to_root'];
        }
        if ($root === '') {
            // <module>/src/Ksfraser/FA_ProductAttributes/Install <- up 4 = module root,
            // up 2 more = FA root (module sits at <root>/modules/FA_ProductAttributes).
            $moduleRoot = dirname(__DIR__, 4);
            $root = dirname($moduleRoot, 2);
        }

        $this->targetPath = rtrim($root, '/\\') . '/inventory/manage/items.php';
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    /**
     * The exact anchor -> replacement pairs. Anchors are verbatim pristine
     * FA 2.4.3 `inventory/manage/items.php` snippets (see the patch file).
     * Escapes: "\t" tab, "\n" newline, "\$" literal dollar inside double quotes.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function replacements(): array
    {
        return array(
            // post_item_write: persist tab POST data after a successful item write.
            array(
                "\t\t\t\$_POST['no_sale'] = \$_POST['editable'] = \$_POST['no_purchase'] =0;\n"
                . "\t\t\tset_focus('NewStockID');\n"
                . "\t\t}\n"
                . "\t\t\$Ajax->activate('_page_body');\n"
                . "\t}\n"
                . "}\n",
                "\t\t\t\$_POST['no_sale'] = \$_POST['editable'] = \$_POST['no_purchase'] =0;\n"
                . "\t\t\tset_focus('NewStockID');\n"
                . "\t\t}\n"
                . "\t\t\$Ajax->activate('_page_body');\n"
                . "\t\t// KSF host hook: modules may persist tab data after the item write.\n"
                . "\t\t\$itemWritten = array('stock_id' => \$_POST['NewStockID']);\n"
                . "\t\thook_invoke_all('post_item_write', \$itemWritten, \$_POST['NewStockID']);\n"
                . "\t}\n"
                . "}\n",
            ),
            // pre_item_delete: modules clean up before delete_item().
            array(
                "\t\t\$stock_id = \$_POST['NewStockID'];\n"
                . "\t\tdelete_item(\$stock_id);\n",
                "\t\t\$stock_id = \$_POST['NewStockID'];\n"
                . "\t\t// KSF host hook: modules may clean up their data before the item delete.\n"
                . "\t\thook_invoke_all('pre_item_delete', \$stock_id);\n"
                . "\t\tdelete_item(\$stock_id);\n",
            ),
            // Tab headers: let modules append tabs before tabbed_content_start().
            array(
                "\t);\n"
                . "\n"
                . "tabbed_content_start('tabs', \$tabs);\n",
                "\t);\n"
                . "\n"
                . "\t// KSF host hook: installed modules may add item tabs (e.g. FA_ProductAttributes).\n"
                . "\t\$moduleTabs = hook_invoke_all('item_display_tab_headers', \$tabs, \$stock_id);\n"
                . "\tif (is_array(\$moduleTabs) && count(\$moduleTabs))\n"
                . "\t\t\$tabs = \$moduleTabs;\n"
                . "\n"
                . "tabbed_content_start('tabs', \$tabs);\n",
            ),
            // Split the core `default: case 'settings':` fallthrough so module tabs
            // are NOT consumed by the settings case.
            array(
                "\tswitch (get_post('_tabs_sel')) {\n"
                . "\t\tdefault:\n"
                . "\t\tcase 'settings':\n"
                . "\t\t\titem_settings(\$stock_id, \$new_item); \n"
                . "\t\t\tbreak;\n",
                "\tswitch (get_post('_tabs_sel')) {\n"
                . "\t\tcase 'settings':\n"
                . "\t\t\titem_settings(\$stock_id, \$new_item); \n"
                . "\t\t\tbreak;\n",
            ),
            // Tab content: new default case dispatches module-provided tabs.
            array(
                "\t\tcase 'status':\n"
                . "\t\t\t\$_GET['stock_id'] = \$stock_id;\n"
                . "\t\t\tinclude_once(\$path_to_root.\"/inventory/inquiry/stock_status.php\");\n"
                . "\t\t\tbreak;\n"
                . "\t};\n",
                "\t\tcase 'status':\n"
                . "\t\t\t\$_GET['stock_id'] = \$stock_id;\n"
                . "\t\t\tinclude_once(\$path_to_root.\"/inventory/inquiry/stock_status.php\");\n"
                . "\t\t\tbreak;\n"
                . "\t\t// KSF host hook: module-provided tabs render their content here.\n"
                . "\t\tdefault:\n"
                . "\t\t\thook_invoke_all('item_display_tab_content', \$stock_id, get_post('_tabs_sel'));\n"
                . "\t\t\tbreak;\n"
                . "\t};\n",
            ),
        );
    }

    /**
     * Whether the given file content already carries the host hook patch.
     */
    public static function isPatchedContent(string $content): bool
    {
        return strpos($content, self::SENTINEL) !== false;
    }

    /**
     * Apply the patch if and only if it is not already applied.
     *
     * @return array{
     *   status: string, // 'already_patched' | 'patched' | 'skipped'
     *   applied: int,
     *   total: int,
     *   message: string
     * }
     */
    public function ensurePatched(): array
    {
        if (!is_file($this->targetPath)) {
            return array(
                'status'  => 'skipped',
                'applied' => 0,
                'total'   => count(self::replacements()),
                'message' => 'items.php not found at ' . $this->targetPath,
            );
        }

        $content = (string)file_get_contents($this->targetPath);
        if (self::isPatchedContent($content)) {
            return array(
                'status'  => 'already_patched',
                'applied' => count(self::replacements()),
                'total'   => count(self::replacements()),
                'message' => 'items.php already carries the KSF host hooks.',
            );
        }

        $applied = 0;
        $skipped = 0;
        foreach (self::replacements() as $pair) {
            if (substr_count($content, $pair[0]) === 1) {
                $content = str_replace($pair[0], $pair[1], $content);
                $applied++;
            } else {
                $skipped++;
            }
        }

        if ($applied < count(self::replacements())) {
            return array(
                'status'  => 'skipped',
                'applied' => $applied,
                'total'   => count(self::replacements()),
                'message' => 'Only ' . $applied . ' of ' . count(self::replacements())
                    . ' pristine anchors matched (' . $skipped . ' skipped); items.php left untouched.',
            );
        }

        $perms = fileperms($this->targetPath);
        $tmp = $this->targetPath . '.ksf.tmp.' . getmypid();
        if (file_put_contents($tmp, $content) === false) {
            return array(
                'status'  => 'skipped',
                'applied' => $applied,
                'total'   => count(self::replacements()),
                'message' => 'Could not write temp patch file.',
            );
        }
        if ($perms !== false) {
            @chmod($tmp, $perms);
        }
        if (!rename($tmp, $this->targetPath)) {
            @unlink($tmp);
            return array(
                'status'  => 'skipped',
                'applied' => $applied,
                'total'   => count(self::replacements()),
                'message' => 'Could not rename temp patch file into place.',
            );
        }

        return array(
            'status'  => 'patched',
            'applied' => $applied,
            'total'   => count(self::replacements()),
            'message' => 'Patched ' . $this->targetPath . ' with ' . $applied . ' KSF item hook insertions.',
        );
    }
}