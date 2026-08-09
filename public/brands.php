<?php

/**
 * Brand / Manufacturer Lookup Admin
 *
 * Manage the dropdown values for Brand and Manufacturer
 * that appear in the Product Identifiers tab.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    $autoload = __DIR__ . '/../../vendor/autoload.php';
}
if (!is_file($autoload)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing composer dependencies. Run: composer install\n";
    exit(1);
}
require_once $autoload;

use Ksfraser\ModulesDAO\Db\PdoDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;

$dsn  = getenv('DB_DSN');
$user = getenv('DB_USER') ?: null;
$pass = getenv('DB_PASS') ?: null;

if (!$dsn) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error: DB_DSN environment variable is not set.\n\n";
    echo "Set the MySQL/MariaDB DSN:\n";
    echo "  DB_DSN='mysql:host=ksf-mariadb;dbname=ksf_fa;charset=utf8'\n";
    echo "  DB_USER=ksf_user\n";
    echo "  DB_PASS=...\n";
    exit(1);
}

$pdo = new PDO($dsn, $user, $pass);
$db  = new PdoDbAdapter($pdo, '');
$dao = new IdentifierLookupsDao($db);

$types = ['brand' => 'Brand', 'manufacturer' => 'Manufacturer'];
$currentType = $_GET['type'] ?? 'brand';
if (!isset($types[$currentType])) {
    $currentType = 'brand';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_entry') {
        $type = $_POST['entry_type'] ?? 'brand';
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '' && isset($types[$type])) {
            $dao->add($type, $name);
        }
        header('Location: ?type=' . rawurlencode($type));
        exit;
    }

    if ($action === 'delete_entry') {
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $returnType = $_POST['entry_type'] ?? 'brand';
        if ($entryId > 0) {
            $dao->delete($entryId);
        }
        header('Location: ?type=' . rawurlencode($returnType));
        exit;
    }
}

$entries = $dao->listByType($currentType);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Brand / Manufacturer Management</title>
  <style>
    body { font-family: sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    fieldset { margin-bottom: 20px; padding: 12px; }
    legend { font-weight: bold; }
    label { display: inline-block; min-width: 120px; }
    input[type="text"] { padding: 4px; }
    .nav a { margin-right: 12px; padding: 6px 12px; text-decoration: none; border: 1px solid #ccc; border-radius: 4px; }
    .nav a.active { background: #0066cc; color: white; border-color: #0066cc; }
    .delete-btn { color: red; background: none; border: none; cursor: pointer; text-decoration: underline; }
  </style>
</head>
<body>
<h1>Brand / Manufacturer Management</h1>
<p>Manage the dropdown values that appear in the Product Identifiers tab.</p>

<div class="nav">
  <?php foreach ($types as $key => $label): ?>
    <a href="?type=<?= htmlspecialchars($key) ?>"
       class="<?= $key === $currentType ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
  <?php endforeach; ?>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($entries)): ?>
      <tr><td colspan="3"><em>No <?= htmlspecialchars($types[$currentType]) ?> entries defined yet.</em></td></tr>
    <?php else: ?>
      <?php foreach ($entries as $e): ?>
        <tr>
          <td><?= (int)($e['id'] ?? 0) ?></td>
          <td><?= htmlspecialchars((string)($e['name'] ?? '')) ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="delete_entry" />
              <input type="hidden" name="entry_id" value="<?= (int)($e['id'] ?? 0) ?>" />
              <input type="hidden" name="entry_type" value="<?= htmlspecialchars($currentType) ?>" />
              <button type="submit" class="delete-btn"
                onclick="return confirm('Delete this <?= htmlspecialchars($types[$currentType]) ?> entry?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<fieldset>
  <legend>Add <?= htmlspecialchars($types[$currentType]) ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="add_entry" />
    <input type="hidden" name="entry_type" value="<?= htmlspecialchars($currentType) ?>" />
    <div>
      <label for="name">Name</label>
      <input type="text" id="name" name="name" required maxlength="128"
        placeholder="<?= htmlspecialchars($types[$currentType]) ?> name" />
    </div>
    <div style="margin-top:8px">
      <button type="submit">Add <?= htmlspecialchars($types[$currentType]) ?></button>
    </div>
  </form>
</fieldset>

</body>
</html>
