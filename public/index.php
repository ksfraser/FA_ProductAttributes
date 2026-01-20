<?php

// Standalone admin entry point.
// Run: php -S localhost:8000 -t public

$autoload = __DIR__ . '/../composer-lib/vendor/autoload.php';
if (!is_file($autoload)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing composer dependencies.\n\n";
    echo "Run:\n  cd composer-lib\n  composer install\n";
    exit(1);
}
require_once $autoload;

use Ksfraser\FA_ProductAttributes\Db\PdoDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\HtmlString;

$dsn = getenv('DB_DSN');
$user = getenv('DB_USER') ?: null;
$pass = getenv('DB_PASS') ?: null;

if (!$dsn) {
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    $dsn = 'sqlite:' . $dataDir . '/product_attributes.sqlite';
}

$pdo = new PDO($dsn, $user, $pass);
$db = new PdoDbAdapter($pdo, '');
$dao = new ProductAttributesDao($db);

$dao->ensureSchema();

$tab = $_GET['tab'] ?? 'categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upsert_category') {
        $dao->upsertCategory(
            trim((string)($_POST['code'] ?? '')),
            trim((string)($_POST['label'] ?? '')),
            trim((string)($_POST['description'] ?? '')),
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['active'])
        );
        header('Location: ?tab=categories');
        exit;
    }

    if ($action === 'upsert_value') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $dao->upsertValue(
            $categoryId,
            trim((string)($_POST['value'] ?? '')),
            trim((string)($_POST['slug'] ?? '')),
            (int)($_POST['sort_order'] ?? 0),
            isset($_POST['active'])
        );
        header('Location: ?tab=values&category_id=' . $categoryId);
        exit;
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Product Attributes</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<h1>Product Attributes</h1>
<nav>
  <a href="?tab=categories">Categories</a>
  <a href="?tab=values">Values</a>
</nav>

<?php if ($tab === 'categories'):
    $cats = $dao->listCategories();
    $table = new HtmlTable(new HtmlString(''));
    $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('class', 'tablestyle2'));
    $table->addNested(TableBuilder::createHeaderRow(['Code', 'Label', 'Sort', 'Active']));
    foreach ($cats as $c) {
        $table->addNested(TableBuilder::createDataRow([
            (string)($c['code'] ?? ''),
            (string)($c['label'] ?? ''),
            (string)($c['sort_order'] ?? 0),
            (string)($c['active'] ?? 0),
        ]));
    }
    $table->toHtml();
?>

<fieldset>
  <legend>Add / Update Category</legend>
  <form method="post">
    <input type="hidden" name="action" value="upsert_category" />
    <div><label>Code</label><input type="text" name="code" required placeholder="size_alpha" /></div>
    <div><label>Label</label><input type="text" name="label" required placeholder="Size (alpha)" /></div>
    <div><label>Description</label><input type="text" name="description" /></div>
    <div><label>Sort order</label><input type="number" name="sort_order" value="0" /></div>
    <div><label>Active</label><input type="checkbox" name="active" checked /></div>
    <div style="margin-top:8px"><button type="submit">Save</button></div>
  </form>
</fieldset>

<?php elseif ($tab === 'values'):
    $categoryId = (int)($_GET['category_id'] ?? 0);
    $cats = $dao->listCategories();
    if ($categoryId === 0 && count($cats) > 0) {
        $categoryId = (int)$cats[0]['id'];
    }
?>

<form method="get">
  <input type="hidden" name="tab" value="values" />
  <label>Category</label>
  <select name="category_id" onchange="this.form.submit()">
    <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
      <option value="<?= htmlspecialchars((string)$id) ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
        <?= htmlspecialchars((string)$c['code']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php
    $values = $categoryId ? $dao->listValues($categoryId) : [];
    $table = new HtmlTable(new HtmlString(''));
    $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('class', 'tablestyle2'));
    $table->addNested(TableBuilder::createHeaderRow(['Value', 'Slug', 'Sort', 'Active']));
    foreach ($values as $v) {
        $table->addNested(TableBuilder::createDataRow([
            (string)($v['value'] ?? ''),
            (string)($v['slug'] ?? ''),
            (string)($v['sort_order'] ?? 0),
            (string)($v['active'] ?? 0),
        ]));
    }
    $table->toHtml();
?>

<fieldset>
  <legend>Add / Update Value</legend>
  <form method="post">
    <input type="hidden" name="action" value="upsert_value" />
    <input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$categoryId) ?>" />
    <div><label>Value</label><input type="text" name="value" required placeholder="Red" /></div>
    <div><label>Slug</label><input type="text" name="slug" required placeholder="red" /></div>
    <div><label>Sort order</label><input type="number" name="sort_order" value="0" /></div>
    <div><label>Active</label><input type="checkbox" name="active" checked /></div>
    <div style="margin-top:8px"><button type="submit">Save</button></div>
  </form>
</fieldset>

<?php endif; ?>

</body>
</html>
