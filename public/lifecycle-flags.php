<?php

/**
 * Lifecycle Flag Definitions Admin
 *
 * Manage the list of storefront flags that appear as checkboxes
 * on the product lifecycle tab.
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
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;

$dsn  = getenv('DB_DSN');
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
$db  = new PdoDbAdapter($pdo, '');
$dao = new LifecycleFlagDefsDao($db);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_flag') {
        $code      = trim((string)($_POST['code'] ?? ''));
        $label     = trim((string)($_POST['label'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active    = isset($_POST['active']) ? 1 : 0;

        if ($code !== '' && $label !== '') {
            $dao->upsertFlag([
                'code'       => $code,
                'label'      => $label,
                'sort_order' => $sortOrder,
                'active'     => $active,
            ]);
        }
        header('Location: ?tab=flags');
        exit;
    }

    if ($action === 'delete_flag') {
        $flagId = (int)($_POST['flag_id'] ?? 0);
        if ($flagId > 0) {
            $dao->deleteFlag($flagId);
        }
        header('Location: ?tab=flags');
        exit;
    }
}

$flags = $dao->listFlags();
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lifecycle Flag Definitions</title>
  <style>
    body { font-family: sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
    fieldset { margin-bottom: 20px; padding: 12px; }
    legend { font-weight: bold; }
    label { display: inline-block; min-width: 120px; }
    input[type="text"], input[type="number"] { padding: 4px; }
    .active-yes { color: green; }
    .active-no { color: #999; }
    .delete-btn { color: red; background: none; border: none; cursor: pointer; text-decoration: underline; }
  </style>
</head>
<body>
<h1>Lifecycle Flag Definitions</h1>
<p>Manage the storefront flags that appear as checkboxes on the product lifecycle tab.</p>

<table>
  <thead>
    <tr>
      <th>Code</th>
      <th>Label</th>
      <th>Sort</th>
      <th>Active</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($flags)): ?>
      <tr><td colspan="5"><em>No flags defined yet.</em></td></tr>
    <?php else: ?>
      <?php foreach ($flags as $f): ?>
        <tr>
          <td><code><?= htmlspecialchars((string)($f['code'] ?? '')) ?></code></td>
          <td><?= htmlspecialchars((string)($f['label'] ?? '')) ?></td>
          <td><?= (int)($f['sort_order'] ?? 0) ?></td>
          <td class="<?= !empty($f['active']) ? 'active-yes' : 'active-no' ?>">
            <?= !empty($f['active']) ? 'Yes' : 'No' ?>
          </td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="delete_flag" />
              <input type="hidden" name="flag_id" value="<?= (int)($f['id'] ?? 0) ?>" />
              <button type="submit" class="delete-btn"
                onclick="return confirm('Delete this flag? All products using it will lose the assignment.')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<fieldset>
  <legend>Add / Update Flag</legend>
  <form method="post">
    <input type="hidden" name="action" value="add_flag" />
    <div>
      <label for="code">Code</label>
      <input type="text" id="code" name="code" required
        placeholder="is_organic" pattern="[a-z_]+" title="Lowercase letters and underscores only" />
      <small>Internal identifier (lowercase, underscores)</small>
    </div>
    <div>
      <label for="label">Label</label>
      <input type="text" id="label" name="label" required
        placeholder="Organic Certified" />
      <small>Display text on the lifecycle tab</small>
    </div>
    <div>
      <label for="sort_order">Sort Order</label>
      <input type="number" id="sort_order" name="sort_order" value="0" min="0" />
    </div>
    <div>
      <label for="active">Active</label>
      <input type="checkbox" id="active" name="active" checked />
    </div>
    <div style="margin-top:8px">
      <button type="submit">Save Flag</button>
    </div>
  </form>
</fieldset>

</body>
</html>
