<?php

// FrontAccounting wrapper admin page.
// Place this repo under FA: modules/FA_ProductAttributes

$page_security = 'SA_PRODUCTATTRIBUTES';

$path_to_root = realpath(__DIR__ . '/../../');
if ($path_to_root === false) {
    $path_to_root = '../..';
}

include($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");

$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
if (is_file($autoload)) {
    require_once $autoload;
}

use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\HtmlString;

page(_("Product Attributes"));

$db = new FrontAccountingDbAdapter();
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
        display_notification(_("Saved category"));
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
        display_notification(_("Saved value"));
    }
}

echo '<div style="margin:8px 0">'
    . '<a href="?tab=categories">Categories</a> | '
    . '<a href="?tab=values">Values</a>'
    . '</div>';

if ($tab === 'categories') {
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

    echo '<br />';

    start_form(true);
    start_table(TABLESTYLE2);
    table_section_title(_("Add / Update Category"));
    text_row(_("Code"), 'code', '', 20, 64);
    text_row(_("Label"), 'label', '', 20, 64);
    text_row(_("Description"), 'description', '', 40, 255);
    small_amount_row(_("Sort order"), 'sort_order', 0);
    check_row(_("Active"), 'active', true);
    hidden('action', 'upsert_category');
    end_table(1);
    submit_center('save', _("Save"));
    end_form();

} else {
    $categoryId = (int)($_GET['category_id'] ?? 0);
    $cats = $dao->listCategories();
    if ($categoryId === 0 && count($cats) > 0) {
        $categoryId = (int)$cats[0]['id'];
    }

    start_form(false);
    start_table(TABLESTYLE2);
    table_section_title(_("Category"));
    echo '<tr><td>' . _("Category") . '</td><td><select name="category_id" onchange="this.form.submit()">';
    foreach ($cats as $c) {
        $id = (int)$c['id'];
        $sel = $id === $categoryId ? ' selected' : '';
        echo '<option value="' . htmlspecialchars((string)$id) . '"' . $sel . '>'
            . htmlspecialchars((string)$c['code'])
            . '</option>';
    }
    echo '</select></td></tr>';
    hidden('tab', 'values');
    end_table(1);
    end_form();

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

    echo '<br />';

    start_form(true);
    start_table(TABLESTYLE2);
    table_section_title(_("Add / Update Value"));
    hidden('action', 'upsert_value');
    hidden('category_id', (string)$categoryId);
    text_row(_("Value"), 'value', '', 20, 64);
    text_row(_("Slug"), 'slug', '', 20, 32);
    small_amount_row(_("Sort order"), 'sort_order', 0);
    check_row(_("Active"), 'active', true);
    end_table(1);
    submit_center('save', _("Save"));
    end_form();
}

end_page();
