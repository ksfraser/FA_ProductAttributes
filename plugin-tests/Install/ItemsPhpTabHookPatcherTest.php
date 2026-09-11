<?php

namespace Ksfraser\FA_ProductAttributes\Test\Install;

use Ksfraser\FA_ProductAttributes\Install\ItemsPhpTabHookPatcher;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for ItemsPhpTabHookPatcher: byte-exact idempotent patch of
 * FA core `inventory/manage/items.php`. The fixture is synthesized from the
 * patcher's own anchors (kept byte-identical to pristine FA 2.4.3 — verified
 * against `fa/2.4.3.tgz` and cross-checked against the live working tree).
 */
class ItemsPhpTabHookPatcherTest extends TestCase
{
    /** @var string */
    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'items_php_patcher_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /**
     * Build a pristine items.php fixture: the patcher anchors joined by harmless
     * comment separators, wrapped in `<?php` so the result is parseable PHP.
     */
    private function buildPristineFixture(): string
    {
        $parts = array('<?php');
        foreach (ItemsPhpTabHookPatcher::replacements() as $i => $pair) {
            $parts[] = '/* pristine anchor region ' . ($i + 1) . ' */';
            $parts[] = $pair[0];
        }
        return implode("\n", $parts);
    }

    public function testPristineFixtureIsNotMarkedPatched(): void
    {
        $this->assertFalse(ItemsPhpTabHookPatcher::isPatchedContent($this->buildPristineFixture()));
    }

    public function testApplySucceedsAndInsertsEveryHookOnce(): void
    {
        $path = $this->tempDir . '/items.php';
        file_put_contents($path, $this->buildPristineFixture());

        $patcher = new ItemsPhpTabHookPatcher($path);
        $result = $patcher->ensurePatched();

        $this->assertSame('patched', $result['status']);
        $this->assertSame(5, $result['applied']);
        $this->assertSame(5, $result['total']);

        $content = file_get_contents($path);

        $this->assertTrue(ItemsPhpTabHookPatcher::isPatchedContent($content));
        // Four distinct hook call sites, each inserted exactly once.
        foreach (array(
            'post_item_write',
            'pre_item_delete',
            'item_display_tab_headers',
            'item_display_tab_content',
        ) as $hook) {
            $this->assertSame(1, substr_count($content, "hook_invoke_all('" . $hook . "'"),
                'hook ' . $hook . ' must occur exactly once');
        }
        // Sentinel marks exactly the 4 inserted blocks.
        $this->assertSame(4, substr_count($content, ItemsPhpTabHookPatcher::SENTINEL));
    }

    public function testSecondRunIsNoOpAndByteIdentical(): void
    {
        $path = $this->tempDir . '/items.php';
        file_put_contents($path, $this->buildPristineFixture());

        $patcher = new ItemsPhpTabHookPatcher($path);
        $patcher->ensurePatched();
        $patchedOnce = file_get_contents($path);

        $second = $patcher->ensurePatched();

        $this->assertSame('already_patched', $second['status']);
        $this->assertSame($patchedOnce, file_get_contents($path),
            're-patching must not touch an already-patched file');
    }

    public function testMissingAnchorRefusesWithoutTouchingFile(): void
    {
        $fixture = $this->buildPristineFixture();
        // Corrupt one region: drop the pre_delete anchor text.
        $pristine = file_get_contents('/dev/null');
        $fixture = str_replace(
            "\t\t\$stock_id = \$_POST['NewStockID'];\n\t\tdelete_item(\$stock_id);\n",
            '',
            $fixture
        );

        $path = $this->tempDir . '/items.php';
        file_put_contents($path, $fixture);
        $before = file_get_contents($path);

        $result = (new ItemsPhpTabHookPatcher($path))->ensurePatched();

        $this->assertSame('skipped', $result['status']);
        $this->assertLessThan($result['total'], $result['applied']);
        $this->assertSame($before, file_get_contents($path),
            'refused patch must leave the file untouched');
    }

    public function testMissingFileReportsSkipped(): void
    {
        $result = (new ItemsPhpTabHookPatcher($this->tempDir . '/nope/items.php'))->ensurePatched();

        $this->assertSame('skipped', $result['status']);
        $this->assertMatchesRegularExpression('/not found/', $result['message']);
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}