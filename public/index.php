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

    if ($action === 'add_assignment') {
      $stockId = trim((string)($_POST['stock_id'] ?? ''));
      $categoryId = (int)($_POST['category_id'] ?? 0);
      $valueId = (int)($_POST['value_id'] ?? 0);
      $sortOrder = (int)($_POST['sort_order'] ?? 0);

      if ($stockId !== '' && $categoryId > 0 && $valueId > 0) {
        $dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);
      }

      header('Location: ?tab=assignments&stock_id=' . rawurlencode($stockId) . '&category_id=' . $categoryId);
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
  <a href="?tab=assignments">Assignments</a>
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

<?php if ($tab === 'assignments'):
    $stockId = trim((string)($_GET['stock_id'] ?? ''));
    $categoryId = (int)($_GET['category_id'] ?? 0);
    $cats = $dao->listCategories();
    if ($categoryId === 0 && count($cats) > 0) {
        $categoryId = (int)$cats[0]['id'];
    }
    $values = $categoryId ? $dao->listValues($categoryId) : [];
?>

<h2>Assignments</h2>

<form method="get">
  <input type="hidden" name="tab" value="assignments" />
  <div>
    <label>Stock ID</label>
    <input type="text" name="stock_id" value="<?= htmlspecialchars($stockId) ?>" placeholder="SKU / stock_id" />
  </div>
  <div style="margin-top:6px">
    <label>Category</label>
    <select name="category_id" onchange="this.form.submit()">
      <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
        <option value="<?= htmlspecialchars((string)$id) ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$c['code']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-top:8px"><button type="submit">Load</button></div>
</form>

<?php if ($stockId !== ''):
    $assignments = $dao->listAssignments($stockId);
    $table = new HtmlTable(new HtmlString(''));
    $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('class', 'tablestyle2'));
    $table->addNested(TableBuilder::createHeaderRow(['Category', 'Value', 'Slug', 'Sort']));
    foreach ($assignments as $a) {
        $table->addNested(TableBuilder::createDataRow([
            (string)($a['category_code'] ?? ''),
            (string)($a['value_label'] ?? ''),
            (string)($a['value_slug'] ?? ''),
            (string)($a['sort_order'] ?? 0),
        ]));
    }
    $table->toHtml();
?>

<fieldset>
  <legend>Add Assignment</legend>
  <form method="post">
    <input type="hidden" name="action" value="add_assignment" />
    <input type="hidden" name="stock_id" value="<?= htmlspecialchars($stockId) ?>" />
    <div><label>Category</label>
      <select name="category_id">
        <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
          <option value="<?= htmlspecialchars((string)$id) ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['code']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Value</label>
      <select name="value_id">
        <?php foreach ($values as $v): $vid = (int)$v['id']; ?>
          <option value="<?= htmlspecialchars((string)$vid) ?>">
            <?= htmlspecialchars((string)$v['value']) ?> (<?= htmlspecialchars((string)$v['slug']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Sort order</label><input type="number" name="sort_order" value="0" /></div>
    <div style="margin-top:8px"><button type="submit">Add</button></div>
  </form>
</fieldset>

<?php else: ?>
<p>Enter a Stock ID to view/add assignments.</p>
<?php endif; ?>

<?php endif; ?>

</body>
</html>
