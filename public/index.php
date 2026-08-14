<?php

/**
 * Product Attributes admin page (FA-native).
 *
 * Runs inside FrontAccounting using FA's database layer (FrontAccountingDbAdapter)
 * instead of a standalone PDO/DB_DSN connection.
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\HtmlString;

// Resolve all relative includes from this module directory.
chdir(__DIR__);

// Load the Composer autoloader.
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($vendorAutoload)) {
    $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
}
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Security area MUST be set before session.inc is included.
$page_security = 'SA_OPEN';

$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

// Required for direct-access module pages using extension security areas.
add_access_extensions();

$tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
$db  = new FrontAccountingDbAdapter($tablePrefix);
$dao = new ProductAttributesDao($db);

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
        $stockId   = trim((string)($_POST['stock_id'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $valueId    = (int)($_POST['value_id'] ?? 0);
        $sortOrder  = (int)($_POST['sort_order'] ?? 0);

        if ($stockId !== '' && $categoryId > 0 && $valueId > 0) {
            $dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);
        }

        header('Location: ?tab=assignments&stock_id=' . rawurlencode($stockId) . '&category_id=' . $categoryId);
        exit;
    }
}

page(_('Product Attributes'), false, false, '', '');

echo '<h1>' . _('Product Attributes') . '</h1>';
echo '<nav>';
echo '<a href="?tab=categories">' . _('Categories') . '</a> &nbsp; ';
echo '<a href="?tab=values">' . _('Values') . '</a> &nbsp; ';
echo '<a href="?tab=assignments">' . _('Assignments') . '</a>';
echo '</nav>';
echo '<br>';

if ($tab === 'categories'):
    $cats = $dao->listCategories();
    $table = new HtmlTable(new HtmlString(''));
    $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('class', 'tablestyle2'));
    $table->addNested(TableBuilder::createHeaderRow([_('Code'), _('Label'), _('Sort'), _('Active')]));
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
  <legend><?php echo _('Add / Update Category'); ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="upsert_category" />
    <div><label><?php echo _('Code'); ?></label><input type="text" name="code" required placeholder="size_alpha" /></div>
    <div><label><?php echo _('Label'); ?></label><input type="text" name="label" required placeholder="Size (alpha)" /></div>
    <div><label><?php echo _('Description'); ?></label><input type="text" name="description" /></div>
    <div><label><?php echo _('Sort order'); ?></label><input type="number" name="sort_order" value="0" /></div>
    <div><label><?php echo _('Active'); ?></label><input type="checkbox" name="active" checked /></div>
    <div style="margin-top:8px"><button type="submit"><?php echo _('Save'); ?></button></div>
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
  <label><?php echo _('Category'); ?></label>
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
    $table->addNested(TableBuilder::createHeaderRow([_('Value'), _('Slug'), _('Sort'), _('Active')]));
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
  <legend><?php echo _('Add / Update Value'); ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="upsert_value" />
    <input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$categoryId) ?>" />
    <div><label><?php echo _('Value'); ?></label><input type="text" name="value" required placeholder="Red" /></div>
    <div><label><?php echo _('Slug'); ?></label><input type="text" name="slug" required placeholder="red" /></div>
    <div><label><?php echo _('Sort order'); ?></label><input type="number" name="sort_order" value="0" /></div>
    <div><label><?php echo _('Active'); ?></label><input type="checkbox" name="active" checked /></div>
    <div style="margin-top:8px"><button type="submit"><?php echo _('Save'); ?></button></div>
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

<h2><?php echo _('Assignments'); ?></h2>

<form method="get">
  <input type="hidden" name="tab" value="assignments" />
  <div>
    <label><?php echo _('Stock ID'); ?></label>
    <input type="text" name="stock_id" value="<?= htmlspecialchars($stockId) ?>" placeholder="SKU / stock_id" />
  </div>
  <div style="margin-top:6px">
    <label><?php echo _('Category'); ?></label>
    <select name="category_id" onchange="this.form.submit()">
      <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
        <option value="<?= htmlspecialchars((string)$id) ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$c['code']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-top:8px"><button type="submit"><?php echo _('Load'); ?></button></div>
</form>

<?php if ($stockId !== ''):
    $assignments = $dao->listAssignments($stockId);
    $table = new HtmlTable(new HtmlString(''));
    $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('class', 'tablestyle2'));
    $table->addNested(TableBuilder::createHeaderRow([_('Category'), _('Value'), _('Slug'), _('Sort')]));
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
  <legend><?php echo _('Add Assignment'); ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="add_assignment" />
    <input type="hidden" name="stock_id" value="<?= htmlspecialchars($stockId) ?>" />
    <div><label><?php echo _('Category'); ?></label>
      <select name="category_id">
        <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
          <option value="<?= htmlspecialchars((string)$id) ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['code']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label><?php echo _('Value'); ?></label>
      <select name="value_id">
        <?php foreach ($values as $v): $vid = (int)$v['id']; ?>
          <option value="<?= htmlspecialchars((string)$vid) ?>">
            <?= htmlspecialchars((string)$v['value']) ?> (<?= htmlspecialchars((string)$v['slug']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label><?php echo _('Sort order'); ?></label><input type="number" name="sort_order" value="0" /></div>
    <div style="margin-top:8px"><button type="submit"><?php echo _('Add'); ?></button></div>
  </form>
</fieldset>

<?php else: ?>
<p><?php echo _('Enter a Stock ID to view/add assignments.'); ?></p>
<?php endif; ?>

<?php endif; ?>

<?php
end_page();
