<?php

/**
 * Product Attributes admin page (FA-native).
 *
 * Runs inside FrontAccounting using FA's database layer (FrontAccountingDbAdapter)
 * instead of a standalone PDO/DB_DSN connection.
 *
 * Each tab's master summary table is rendered with the reusable
 * Ksfraser\Frontaccounting\HTML\MasterSummaryTable component (ksf_FA_Common),
 * which carries the record id + _tabs_sel through row actions so deletes and
 * edits return to the same tab (the no-hard-refresh pattern from issue #24).
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\Frontaccounting\HTML\MasterSummaryTable;
use Ksfraser\Frontaccounting\HTML\TabContext;

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

// Preload the FA database adapter before session.inc registers the other
// modules' autoloaders. Some deployed modules vendor older ksf-modules-dao
// copies that declare the same class name; preloading here guarantees this
// module's newer implementation is the one used for the request.
class_exists(\Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter::class);

// Security area MUST be set before session.inc is included.
$page_security = 'SA_OPEN';

$path_to_root = "../../..";
include_once($path_to_root . "/includes/session.inc");

// Required for direct-access module pages using extension security areas.
add_access_extensions();

$tablePrefix = defined('TB_PREF') ? (string)TB_PREF : '0_';
$dbAdapter  = new FrontAccountingDbAdapter($tablePrefix);
$dao = new ProductAttributesDao($dbAdapter);

/**
 * Build the MasterSummaryTable for the active sub-tab.
 *
 * @param string                $tab        Active sub-tab ('categories' | 'values' | 'assignments')
 * @param ProductAttributesDao  $dao        Data access object
 * @param int                   $categoryId Selected category id (values/assignments)
 * @param string                $stockId    Selected stock id (assignments)
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_build_summary(string $tab, ProductAttributesDao $dao, int $categoryId, string $stockId): MasterSummaryTable
{
    $opts = [
        'record_id_field' => 'id',
        'row_id_field'    => 'id',
        'tab_sel'         => $tab,
        'show_footer'     => false,
        'ajax'            => false,
    ];

    if ($tab === 'values') {
        return new MasterSummaryTable(
            [
                ['key' => 'value', 'label' => _('Value')],
                ['key' => 'slug', 'label' => _('Slug')],
                ['key' => 'sort_order', 'label' => _('Sort')],
                ['key' => 'active', 'label' => _('Active')],
            ],
            $dao->listValues($categoryId),
            ['edit' => true, 'delete' => true],
            array_merge($opts, ['delete_confirm_message' => _('Delete this value and its assignments?')])
        );
    }

    if ($tab === 'assignments') {
        return new MasterSummaryTable(
            [
                ['key' => 'category_code', 'label' => _('Category')],
                ['key' => 'value_label', 'label' => _('Value')],
                ['key' => 'value_slug', 'label' => _('Slug')],
                ['key' => 'sort_order', 'label' => _('Sort')],
            ],
            $stockId !== '' ? $dao->listAssignments($stockId) : [],
            ['delete' => true],
            array_merge($opts, ['delete_confirm_message' => _('Remove this assignment?')])
        );
    }

    return new MasterSummaryTable(
        [
            ['key' => 'code', 'label' => _('Code')],
            ['key' => 'label', 'label' => _('Label')],
            ['key' => 'sort_order', 'label' => _('Sort')],
            ['key' => 'active', 'label' => _('Active')],
        ],
        $dao->listCategories(),
        ['edit' => true, 'delete' => true],
        array_merge($opts, ['delete_confirm_message' => _('Delete this category, its values and assignments?')])
    );
}

/**
 * Delete the record identified by a row-action button on the active tab.
 *
 * @param string                $tab   Active sub-tab
 * @param ProductAttributesDao  $dao   Data access object
 * @param int                   $rowId Record id
 * @return void
 *
 * @since 1.0.0
 */
function pa_delete_row(string $tab, ProductAttributesDao $dao, int $rowId): void
{
    if ($rowId <= 0) {
        return;
    }

    if ($tab === 'values') {
        $dao->deleteValue($rowId);
        return;
    }

    if ($tab === 'assignments') {
        $dao->deleteAssignment($rowId);
        return;
    }

    $dao->deleteCategory($rowId);
}

/**
 * Build the redirect target for the active sub-tab, preserving the selected
 * category/stock context.
 *
 * @param string $tab        Active sub-tab
 * @param int    $categoryId Selected category id
 * @param string $stockId    Selected stock id
 * @return string Relative redirect URL
 *
 * @since 1.0.0
 */
function pa_redirect_for(string $tab, int $categoryId, string $stockId): string
{
    $target = '?tab=' . rawurlencode($tab);

    if ($tab === 'values' && $categoryId > 0) {
        $target .= '&category_id=' . $categoryId;
    }

    if ($tab === 'assignments' && $stockId !== '') {
        $target .= '&stock_id=' . rawurlencode($stockId);
    }

    return $target;
}

$allowedTabs = ['categories', 'values', 'assignments'];
$tab = (string) ($_GET['tab'] ?? 'categories');
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'categories';
}

// A POST from a MasterSummaryTable form carries the active tab in _tabs_sel;
// prefer it so a row action returns to the same tab.
$postTab = TabContext::fromPost($_POST, 'id')->getTabSel();
if (in_array($postTab, $allowedTabs, true)) {
    $tab = $postTab;
}

$categoryId = (int) ($_GET['category_id'] ?? ($_POST['category_id'] ?? 0));
$stockId    = trim((string) ($_GET['stock_id'] ?? ($_POST['stock_id'] ?? '')));

$cats = $dao->listCategories();
if ($categoryId === 0 && count($cats) > 0) {
    $categoryId = (int) $cats[0]['id'];
}

$editRowId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rowAction = pa_build_summary($tab, $dao, $categoryId, $stockId)->getPostedAction($_POST);
    $action    = (string) ($_POST['action'] ?? '');

    if ($rowAction !== null) {
        $rowId = (int) $rowAction['id'];

        if ($rowAction['action'] === 'delete') {
            pa_delete_row($tab, $dao, $rowId);
            display_notification(_('Record deleted.'));

            // Re-query so the summary table and dropdowns no longer reference
            // the deleted record in the same request (issue #57).
            $cats = $dao->listCategories();
        } else {
            // Edit: fall through to render the form prefilled with this record.
            $editRowId = $rowId;
        }
    }

    if ($action === 'upsert_category') {
        $editId = (int) ($_POST['id'] ?? 0);
        $dao->upsertCategory(
            trim((string) ($_POST['code'] ?? '')),
            trim((string) ($_POST['label'] ?? '')),
            trim((string) ($_POST['description'] ?? '')),
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['active']),
            $editId > 0 ? $editId : null
        );
        header('Location: ' . pa_redirect_for('categories', $categoryId, $stockId));
        exit;
    }

    if ($action === 'upsert_value') {
        $catId  = (int) ($_POST['category_id'] ?? 0);
        $editId = (int) ($_POST['id'] ?? 0);
        $dao->upsertValue(
            $catId,
            trim((string) ($_POST['value'] ?? '')),
            trim((string) ($_POST['slug'] ?? '')),
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['active']),
            $editId > 0 ? $editId : 0
        );
        header('Location: ' . pa_redirect_for('values', $catId > 0 ? $catId : $categoryId, $stockId));
        exit;
    }

    if ($action === 'add_assignment') {
        $sId       = trim((string) ($_POST['stock_id'] ?? ''));
        $catId     = (int) ($_POST['category_id'] ?? 0);
        $valueId   = (int) ($_POST['value_id'] ?? 0);
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($sId !== '' && $catId > 0 && $valueId > 0) {
            $dao->assignValues($sId, [
                ['category_id' => $catId, 'value_id' => $valueId, 'sort_order' => $sortOrder],
            ]);
        }

        header('Location: ' . pa_redirect_for('assignments', $catId, $sId));
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
    $editCatId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
    $editing = null;
    foreach ($cats as $c) {
        if ((int) ($c['id'] ?? 0) === $editCatId) {
            $editing = $c;
            break;
        }
    }
    echo '<form method="post">';
    pa_build_summary('categories', $dao, $categoryId, $stockId)->render();
    echo '</form>';
?>

<fieldset>
  <legend><?php echo $editing ? _('Edit Category') : _('Add Category'); ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="upsert_category" />
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>" />
    <?php endif; ?>
    <div><label><?php echo _('Code'); ?></label><input type="text" name="code" required placeholder="size_alpha" value="<?= htmlspecialchars((string)($editing['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
    <div><label><?php echo _('Label'); ?></label><input type="text" name="label" required placeholder="Size (alpha)" value="<?= htmlspecialchars((string)($editing['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
    <div><label><?php echo _('Description'); ?></label><input type="text" name="description" value="<?= htmlspecialchars((string)($editing['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
    <div><label><?php echo _('Sort order'); ?></label><input type="number" name="sort_order" value="<?= (int)($editing['sort_order'] ?? 0) ?>" /></div>
    <div><label><?php echo _('Active'); ?></label><input type="checkbox" name="active" <?= ($editing ? ((int)($editing['active'] ?? 1) === 1) : true) ? 'checked' : '' ?> /></div>
    <div style="margin-top:8px"><button type="submit"><?php echo _('Save'); ?></button>
      <?php if ($editing): ?>
        <a href="?tab=categories" style="margin-left:8px"><?php echo _('Cancel'); ?></a>
      <?php endif; ?>
    </div>
  </form>
</fieldset>

<?php elseif ($tab === 'values'):
    $values = $categoryId ? $dao->listValues($categoryId) : [];
    $editValId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
    $editingValue = null;
    foreach ($values as $v) {
        if ((int) ($v['id'] ?? 0) === $editValId) {
            $editingValue = $v;
            break;
        }
    }
?>

<form method="get">
  <input type="hidden" name="tab" value="values" />
  <label><?php echo _('Category'); ?></label>
  <select name="category_id" onchange="this.form.submit()">
      <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
        <option value="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$c['code'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</fieldset>

<?php
    echo '<form method="post">';
    echo '<input type="hidden" name="category_id" value="' . htmlspecialchars((string)$categoryId, ENT_QUOTES, 'UTF-8') . '" />';
    pa_build_summary('values', $dao, $categoryId, $stockId)->render();
    echo '</form>';
?>

<fieldset>
  <legend><?php echo $editingValue ? _('Edit Value') : _('Add Value'); ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="upsert_value" />
    <input type="hidden" name="category_id" value="<?= htmlspecialchars((string)$categoryId, ENT_QUOTES, 'UTF-8') ?>" />
    <?php if ($editingValue): ?>
      <input type="hidden" name="id" value="<?= (int)$editingValue['id'] ?>" />
    <?php endif; ?>
    <div><label><?php echo _('Value'); ?></label><input type="text" name="value" required placeholder="Red" value="<?= htmlspecialchars((string)($editingValue['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
    <div><label><?php echo _('Slug'); ?></label><input type="text" name="slug" required placeholder="red" value="<?= htmlspecialchars((string)($editingValue['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
    <div><label><?php echo _('Sort order'); ?></label><input type="number" name="sort_order" value="<?= (int)($editingValue['sort_order'] ?? 0) ?>" /></div>
    <div><label><?php echo _('Active'); ?></label><input type="checkbox" name="active" <?= ($editingValue ? ((int)($editingValue['active'] ?? 1) === 1) : true) ? 'checked' : '' ?> /></div>
    <div style="margin-top:8px"><button type="submit"><?php echo _('Save'); ?></button>
      <?php if ($editingValue): ?>
        <a href="?tab=values&category_id=<?= $categoryId ?>" style="margin-left:8px"><?php echo _('Cancel'); ?></a>
      <?php endif; ?>
    </div>
  </form>
</fieldset>

<?php else: /* assignments */
    $values = $categoryId ? $dao->listValues($categoryId) : [];
?>

<h2><?php echo _('Assignments'); ?></h2>

<form method="get">
  <input type="hidden" name="tab" value="assignments" />
  <div>
    <label><?php echo _('Stock Item'); ?></label>
    <select name="stock_id">
      <option value=""><?php echo _('-- Select Stock Item --'); ?></option>
      <?php foreach ($dao->listStockItems() as $s): ?>
        <option value="<?= htmlspecialchars((string)$s['stock_id'], ENT_QUOTES, 'UTF-8') ?>" <?= $stockId === (string)$s['stock_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$s['stock_id'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)($s['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-top:6px">
    <label><?php echo _('Category'); ?></label>
    <select name="category_id" onchange="this.form.submit()">
      <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
        <option value="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$c['code'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div style="margin-top:8px"><button type="submit"><?php echo _('Load'); ?></button></div>
</form>

<?php if ($stockId !== ''): ?>
<?php
    echo '<form method="post">';
    echo '<input type="hidden" name="category_id" value="' . htmlspecialchars((string)$categoryId, ENT_QUOTES, 'UTF-8') . '" />';
    echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId, ENT_QUOTES, 'UTF-8') . '" />';
    pa_build_summary('assignments', $dao, $categoryId, $stockId)->render();
    echo '</form>';
?>

<fieldset>
  <legend><?php echo _('Add Assignment'); ?></legend>
  <form method="post">
    <input type="hidden" name="action" value="add_assignment" />
    <input type="hidden" name="stock_id" value="<?= htmlspecialchars($stockId, ENT_QUOTES, 'UTF-8') ?>" />
    <div><label><?php echo _('Category'); ?></label>
      <select name="category_id" id="admin_pa_category_select"
        onchange="var v=document.getElementById('admin_pa_value_select');v.innerHTML='<option value="">Loading...</option>';fetch('ajax_get_values.php?category_id='+this.value).then(function(r){return r.json()}).then(function(d){var h='<option value=\"\">-- Select Value --</option>';for(var i=0;i<d.length;i++){h+='<option value=\"'+d[i].id+'\">'+d[i].value+' ('+d[i].slug+')</option>'}v.innerHTML=h;})">
        <?php foreach ($cats as $c): $id = (int)$c['id']; ?>
          <option value="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>" <?= $id === $categoryId ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['code'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label><?php echo _('Value'); ?></label>
      <select name="value_id" id="admin_pa_value_select">
        <?php foreach ($values as $v): $vid = (int)$v['id']; ?>
          <option value="<?= htmlspecialchars((string)$vid, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string)$v['value'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$v['slug'], ENT_QUOTES, 'UTF-8') ?>)
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
