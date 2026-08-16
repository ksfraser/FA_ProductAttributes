<?php

/**
 * Subprocess harness for rendering the FA-native admin pages.
 *
 * Each render runs in a fresh PHP process so the page-level functions
 * (pa_build_summary, pa_flags_summary, pa_lookups_summary, ...) and the FA
 * function mocks cannot collide between pages or between tabs of the same
 * page.
 *
 * A fixture-aware db_query is defined BEFORE tests/bootstrap.php loads the
 * FAMock library, so this implementation wins the function_exists() guard in
 * FaDbStubs. For each SELECT it populates $GLOBALS['__fa_result_set'] from the
 * passed fixture map, keyed by unique SQL substrings (not full SQL text, so
 * the test does not need to reproduce the DAO's parameter substitution).
 *
 * Usage: php render-admin-page.php '<json args>'
 *   json args: {
 *     "page":     "index|lifecycle-flags|brands",
 *     "get":      {"tab": "categories", ...},
 *     "post":     {...},                      // optional
 *     "fixtures": {"<sql substring>": [rows]} // optional
 *   }
 *
 * Prints JSON: {"html": "<rendered output>"}
 *
 * @package FA_ProductAttributes
 */

// Must be defined before FAMock loads so this fixture-aware implementation
// wins the function_exists() guard in FaDbStubs.
if (!function_exists('db_query')) {
    function db_query(string $sql, $error = null)
    {
        $GLOBALS['__fa_last_sql'] = $sql;
        $GLOBALS['__fa_last_update_matched'] = false;

        if (stripos($sql, 'SELECT') === 0) {
            $rows = [];
            foreach (($GLOBALS['__fa_fixtures'] ?? []) as $key => $fixtureRows) {
                if ($key !== '' && strpos($sql, $key) !== false) {
                    $rows = $fixtureRows;
                    break;
                }
            }
            $GLOBALS['__fa_result_set'][$sql] = $rows;
            $GLOBALS['__fa_result_pos'][$sql] = 0;
        }

        return $sql;
    }
}

require_once __DIR__ . '/../bootstrap.php';

$args = json_decode((string) ($argv[1] ?? '{}'), true);
if (!is_array($args)) {
    fwrite(STDERR, "invalid args\n");
    exit(1);
}

$_GET = $args['get'] ?? [];
$_POST = $args['post'] ?? [];
$_SERVER['REQUEST_METHOD'] = empty($_POST) ? 'GET' : 'POST';

$GLOBALS['__fa_fixtures'] = $args['fixtures'] ?? [];
$GLOBALS['__fa_result_set'] = [];
$GLOBALS['__fa_result_pos'] = [];
$GLOBALS['__fa_table'] = [];

$page = (string) ($args['page'] ?? '');
$pageFile = __DIR__ . '/../../public/' . $page . '.php';
if (!is_file($pageFile)) {
    fwrite(STDERR, 'unknown page: ' . $page . "\n");
    exit(1);
}

// The pages include FA's session.inc which does not exist in the test
// environment; FAMock already provides all the functions session.inc would
// wire up, so suppress the include warning for the render.
$reporting = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ob_start();
include $pageFile;
error_reporting($reporting);
$html = (string) ob_get_clean();

echo json_encode(['html' => $html]);
exit(0);
