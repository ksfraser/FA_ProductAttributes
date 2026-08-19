<?php
/**
 * AJAX endpoint: returns JSON values for a given attribute category.
 *
 * Called by inline onchange handlers on category <select> elements
 * in the Product Attributes tab and admin page.
 *
 * @package FA_ProductAttributes
 */

$path_to_root = "../../..";
$page_security = 'SA_OPEN';

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

header('Content-Type: application/json; charset=utf-8');

$categoryId = (int)($_GET['category_id'] ?? $_POST['category_id'] ?? 0);
if ($categoryId <= 0) {
    echo json_encode([]);
    exit;
}

vendorAutoload();

$tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
$dbAdapter   = new \Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter($tablePrefix);
$dao         = new \Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao($dbAdapter);

$values = $dao->getValuesForCategory($categoryId);

$result = [];
foreach ($values as $v) {
    $result[] = [
        'id'    => (int)$v['id'],
        'value' => (string)$v['value'],
        'slug'  => (string)($v['slug'] ?? ''),
    ];
}

echo json_encode($result);

function vendorAutoload(): void
{
    $paths = [
        __DIR__ . '/../vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php',
        dirname(__DIR__) . '/../../vendor/autoload.php',
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
}
