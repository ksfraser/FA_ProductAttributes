<?php

// FrontAccounting wrapper admin page.
// Place this repo under FA: modules/FA_ProductAttributes

$page_security = 'SA_PRODUCTATTRIBUTES';

$path_to_root = '../..';

include($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");

// Force use of master database for admin operations
global $db_connections, $db;
$company = $_SESSION['wa_current_user']->company;
$master_db = mysql_connect($db_connections[$company]['host'], $db_connections[$company]['user'], $db_connections[$company]['password']);
mysql_select_db($db_connections[$company]['name'], $master_db);
$db = $master_db;

// Debug: check path
display_notification("path_to_root: " . $path_to_root);
display_notification("session.inc exists: " . (file_exists($path_to_root . "/includes/session.inc") ? "yes" : "no"));

// Manually define FA_ROOT if it's not set
if (!defined('FA_ROOT')) {
    define('FA_ROOT', $path_to_root . '/');
}

// Fix TB_PREF if it's incorrectly set
if (isset($_SESSION['wa_current_user']->company)) {
    define('TB_PREF', $_SESSION['wa_current_user']->company . '_');
}

$autoload = __DIR__ . "/composer-lib/vendor/autoload.php";
if (is_file($autoload)) {
    require_once $autoload;
}

page(_("Product Attributes"));

use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\HtmlString;

try {
    $db_adapter = new FrontAccountingDbAdapter();
    $dao = new ProductAttributesDao($db_adapter);
    $dao->ensureSchema();
} catch (Exception $e) {
    display_error("Database error: " . $e->getMessage());
    end_page();
    exit;
}

// Debug: show table prefix
if (defined('TB_PREF')) {
    display_notification("TB_PREF defined: " . constant('TB_PREF'));
} else {
    display_notification("TB_PREF not defined");
}
display_notification("Table prefix: " . $db->getTablePrefix());

// Debug: check if tables exist
$query = "SELECT TABLE_NAME FROM information_schema.tables WHERE LOWER(table_schema) = LOWER(DATABASE()) AND table_name LIKE '" . $db->getTablePrefix() . "product_attribute_%'";
display_notification("Query: " . $query);
$tables = $db->selectAll($query);
display_notification("Product attribute tables found: " . count($tables));

// Debug: test db connection
$test = $db->selectAll("SELECT 1 FROM " . $db->getTablePrefix() . "stock_master LIMIT 1");
display_notification("Test query on FA table result count: " . count($test));

// Debug: current company
if (isset($_SESSION['wa_current_user']->company)) {
    display_notification("Current company: " . $_SESSION['wa_current_user']->company);
    global $db_connections;
    if (isset($db_connections[$_SESSION['wa_current_user']->company]['name'])) {
        display_notification("DB name: " . $db_connections[$_SESSION['wa_current_user']->company]['name']);
    }
} else {
    display_notification("Current company not set");
}

try {
    $db = new FrontAccountingDbAdapter();
    $dao = new ProductAttributesDao($db);
    $dao->ensureSchema();
} catch (Exception $e) {
    display_error("Database error: " . $e->getMessage());
    end_page();
    exit;
}

// Debug: show table prefix
if (defined('TB_PREF')) {
    display_notification("TB_PREF defined: " . constant('TB_PREF'));
} else {
    display_notification("TB_PREF not defined");
}
display_notification("Table prefix: " . $db->getTablePrefix());

// Debug: check if tables exist
$query = "SELECT TABLE_NAME FROM information_schema.tables WHERE LOWER(table_schema) = LOWER(DATABASE()) AND table_name LIKE '" . $db->getTablePrefix() . "product_attribute_%'";
display_notification("Query: " . $query);
$tables = $db->selectAll($query);
display_notification("Product attribute tables found: " . count($tables));

// Debug: test db connection
$test = $db->selectAll("SELECT 1 FROM " . $db->getTablePrefix() . "stock_master LIMIT 1");
display_notification("Test query on FA table result count: " . count($test));

// Debug: current company
if (isset($_SESSION['wa_current_user']->company)) {
    display_notification("Current company: " . $_SESSION['wa_current_user']->company);
    global $db_connections;
    if (isset($db_connections[$_SESSION['wa_current_user']->company]['name'])) {
        display_notification("DB name: " . $db_connections[$_SESSION['wa_current_user']->company]['name']);
        display_notification("DB host: " . $db_connections[$_SESSION['wa_current_user']->company]['host']);
    }
} else {
    display_notification("Current company not set");
}

$tab = $_GET['tab'] ?? 'categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'upsert_category') {
            $dao->upsertCategory(
                trim((string)($_POST['code'] ?? '')),
                trim((string)($_POST['label'] ?? '')),
                trim((string)($_POST['description'] ?? '')),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['active'])
            );
            // Debug: check count after save
            $check = $db->selectAll("SELECT COUNT(*) as cnt FROM " . $db->getTablePrefix() . "product_attribute_categories");
            display_notification("Categories count after save: " . ($check[0]['cnt'] ?? 'error'));
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

        if ($action === 'add_assignment') {
            $stockId = trim((string)($_POST['stock_id'] ?? ''));
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $valueId = (int)($_POST['value_id'] ?? 0);
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($stockId !== '' && $categoryId > 0 && $valueId > 0) {
                $dao->addAssignment($stockId, $categoryId, $valueId, $sortOrder);
                display_notification(_("Added assignment"));
            }
        }
    } catch (Exception $e) {
        display_error("Error saving: " . $e->getMessage());
    }
}

echo '<div style="margin:8px 0">'
    . '<a href="?tab=categories">Categories</a> | '
    . '<a href="?tab=values">Values</a> | '
    . '<a href="?tab=assignments">Assignments</a>'
    . '</div>';

if ($tab === 'categories') {
    try {
        $cats = $dao->listCategories();

        if (count($cats) > 0) {
            // Use FA's standard table functions
            start_table(TABLESTYLE2);
            $th = array(_("Code"), _("Label"), _("Sort"), _("Active"));
            table_header($th);

            foreach ($cats as $c) {
                start_row();
                label_cell($c['code'] ?? '');
                label_cell($c['label'] ?? '');
                label_cell($c['sort_order'] ?? 0);
                label_cell($c['active'] ?? 0 ? _("Yes") : _("No"));
                end_row();
            }
            end_table();
        }

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
    } catch (Exception $e) {
        display_error("Error in categories tab: " . $e->getMessage());
    }
} else if ($tab === 'values') {
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

    $table = \Ksfraser\HTML\Elements\HtmlTable::createFaTable(2); // TABLESTYLE2
    $table->addNested(TableBuilder::createHeaderRow(['Value', 'Slug', 'Sort', 'Active']));
    foreach ($values as $v) {
        $table->addNested(TableBuilder::createDataRow([
            (string)($v['value'] ?? ''),
            (string)($v['slug'] ?? ''),
            (string)($v['sort_order'] ?? 0),
            (string)($v['active'] ?? 0 ? 'Yes' : 'No'),
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
} else {
    $stockId = trim((string)($_GET['stock_id'] ?? ''));
    $categoryId = (int)($_GET['category_id'] ?? 0);
    $cats = $dao->listCategories();
    if ($categoryId === 0 && count($cats) > 0) {
        $categoryId = (int)$cats[0]['id'];
    }
    $values = $categoryId ? $dao->listValues($categoryId) : [];

    start_form(false);
    start_table(TABLESTYLE2);
    table_section_title(_("Assignments"));
    text_row(_("Stock ID"), 'stock_id', $stockId, 20, 32);
    echo '<tr><td>' . _("Category") . '</td><td><select name="category_id" onchange="this.form.submit()">';
    foreach ($cats as $c) {
        $id = (int)$c['id'];
        $sel = $id === $categoryId ? ' selected' : '';
        echo '<option value="' . htmlspecialchars((string)$id) . '"' . $sel . '>'
            . htmlspecialchars((string)$c['code'])
            . '</option>';
    }
    echo '</select></td></tr>';
    hidden('tab', 'assignments');
    end_table(1);
    submit_center('load', _("Load"));
    end_form();

    if ($stockId !== '') {
        $assignments = $dao->listAssignments($stockId);

        $table = \Ksfraser\HTML\Elements\HtmlTable::createFaTable(2); // TABLESTYLE2
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

        echo '<br />';

        start_form(true);
        start_table(TABLESTYLE2);
        table_section_title(_("Add Assignment"));
        hidden('action', 'add_assignment');
        hidden('tab', 'assignments');
        hidden('stock_id', $stockId);
        hidden('category_id', (string)$categoryId);

        echo '<tr><td>' . _("Value") . '</td><td><select name="value_id">';
        foreach ($values as $v) {
            $vid = (int)$v['id'];
            echo '<option value="' . htmlspecialchars((string)$vid) . '">'
                . htmlspecialchars((string)$v['value'])
                . ' (' . htmlspecialchars((string)$v['slug']) . ')'
                . '</option>';
        }
        echo '</select></td></tr>';

        small_amount_row(_("Sort order"), 'sort_order', 0);
        end_table(1);
        submit_center('add', _("Add"));
        end_form();
    }
}

end_page();
