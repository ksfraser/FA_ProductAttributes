<?php

/**
 * Product Attributes admin page (FA-native) — consolidated hub.
 *
 * One page, four sections. The three former admin pages (index.php, brands.php,
 * lifecycle-flags.php) moved here as sections, plus a fourth "Conditions"
 * section:
 *
 *   ?tab=attributes&sub=categories|values|assignments   Product attribute taxonomy
 *   ?tab=conditions                                     Condition definitions (+ site default)
 *   ?tab=flags                                          Lifecycle flag definitions
 *   ?tab=brands[&type=brand|manufacturer]               Brand / Manufacturer lookups
 *
 * Legacy deep links (?tab=categories/values/assignments, and the old
 * lifecycle-flags.php / brands.php URLs redirect here) keep working.
 *
 * Runs inside FrontAccounting using FA's database layer (FrontAccountingDbAdapter)
 * instead of a standalone PDO/DB_DSN connection.
 *
 * Each section's master summary table is rendered with the reusable
 * Ksfraser\Frontaccounting\HTML\MasterSummaryTable component (ksf_FA_Common),
 * which carries the record id + _tabs_sel through row actions so deletes and
 * edits return to the same section (the no-hard-refresh pattern from issue #24).
 *
 * @package FA_ProductAttributes
 */

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\FA_ProductAttributes\Dao\IdentifierLookupsDao;
use Ksfraser\FA_ProductAttributes\Dao\LifecycleFlagDefsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\UI\AddAssignmentForm;
use Ksfraser\Frontaccounting\HTML\MasterSummaryTable;
use Ksfraser\Frontaccounting\HTML\TabContext;

// Resolve all relative includes from this module directory. Apache mod_php
// persists CWD across requests, so an un-restored chdir() here blanks every
// later request handled by this worker (blank-page "on refresh" incident).
// Restore on shutdown, targeting DOCUMENT_ROOT so this self-heals workers that
// were already parked in a module directory.
chdir(__DIR__);
register_shutdown_function(function () {
    $target = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : getcwd();
    if (is_string($target) && is_dir($target)) {
        @chdir($target);
    }
});

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
$flagsDao = new LifecycleFlagDefsDao($dbAdapter);
$lookupsDao = new IdentifierLookupsDao($dbAdapter);

// Section routing. The attributes section keeps its three historical sub-tabs;
// legacy ?tab=categories|values|assignments deep links map into it.
$attributeSubTabs = ['categories', 'values', 'assignments'];
$allowedSections  = ['attributes', 'conditions', 'flags', 'brands'];
$types            = ['brand' => 'Brand', 'manufacturer' => 'Manufacturer'];

$section = (string) ($_GET['tab'] ?? 'attributes');
$sub     = (string) ($_GET['sub'] ?? 'categories');
$lookupType = (string) ($_GET['type'] ?? 'brand');

if (in_array($section, $attributeSubTabs, true)) {
    $sub = $section;
    $section = 'attributes';
}
if (!in_array($section, $allowedSections, true)) {
    $section = 'attributes';
}
if (!in_array($sub, $attributeSubTabs, true)) {
    $sub = 'categories';
}
if (!isset($types[$lookupType])) {
    $lookupType = 'brand';
}

/**
 * Build the MasterSummaryTable for a Product Attributes sub-tab.
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
 * Build the MasterSummaryTable of categories assigned to a stock item
 * (category-level assignments), with a per-row unassign action.
 *
 * @param string              $stockId Selected stock id
 * @param ProductAttributesDao $dao    Data access object
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_assigned_categories_summary(string $stockId, ProductAttributesDao $dao): MasterSummaryTable
{
    $rows = [];
    foreach ($stockId !== '' ? $dao->listCategoryAssignments($stockId) : [] as $cat) {
        $count = count($dao->listActiveValues((int) $cat['id']));
        $rows[] = [
            'id'            => (int) ($cat['id'] ?? 0),
            'category_label'=> (string) ($cat['label'] ?? ''),
            'active_values' => $count . ' ' . _('values'),
        ];
    }

    return new MasterSummaryTable(
        [
            ['key' => 'category_label', 'label' => _('Category')],
            ['key' => 'active_values', 'label' => _('Active Values')],
        ],
        $rows,
        $stockId !== '' ? ['delete' => true] : [],
        [
            'record_id_field'        => 'id',
            'row_id_field'           => 'id',
            'tab_sel'                => 'assign_categories',
            'show_footer'            => false,
            'ajax'                   => false,
            'empty_message'          => _('No categories assigned to this item yet.'),
            'delete_confirm_message' => _('Unassign this category from the item?'),
        ]
    );
}

/**
 * Delete the record identified by a row-action button on a Product Attributes
 * sub-tab.
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
 * Build the redirect target for a Product Attributes sub-tab, preserving the
 * selected category/stock context.
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
    $target = '?tab=attributes&sub=' . rawurlencode($tab);

    if ($tab === 'values' && $categoryId > 0) {
        $target .= '&category_id=' . $categoryId;
    }

    if ($tab === 'assignments' && $stockId !== '') {
        $target .= '&stock_id=' . rawurlencode($stockId);
    }

    return $target;
}

/**
 * Build the MasterSummaryTable for lifecycle flag definitions.
 *
 * @param array<int, array<string, mixed>> $flags Flag definition rows
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_flags_summary(array $flags): MasterSummaryTable
{
    $rows = [];
    foreach ($flags as $f) {
        $rows[] = [
            'id'         => (int) ($f['id'] ?? 0),
            'code'       => (string) ($f['code'] ?? ''),
            'label'      => (string) ($f['label'] ?? ''),
            'sort_order' => (int) ($f['sort_order'] ?? 0),
            'active'     => !empty($f['active']) ? _('Yes') : _('No'),
        ];
    }

    return new MasterSummaryTable(
        [
            ['key' => 'code', 'label' => _('Code')],
            ['key' => 'label', 'label' => _('Label')],
            ['key' => 'sort_order', 'label' => _('Sort')],
            ['key' => 'active', 'label' => _('Active')],
        ],
        $rows,
        ['edit' => true, 'delete' => true],
        [
            'record_id_field'      => 'id',
            'row_id_field'         => 'id',
            'tab_sel'              => 'flags',
            'show_footer'          => false,
            'ajax'                 => false,
            'empty_message'        => _('No flags defined yet.'),
            'delete_confirm_message' => _('Delete this flag? All products using it will lose the assignment.'),
        ]
    );
}

/**
 * Build the MasterSummaryTable for condition definitions.
 *
 * @param array<int, array<string, mixed>> $conditions Condition definition rows
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_conditions_summary(array $conditions): MasterSummaryTable
{
    $rows = [];
    foreach ($conditions as $c) {
        $rows[] = [
            'id'         => (int) ($c['id'] ?? 0),
            'code'       => (string) ($c['code'] ?? ''),
            'label'      => (string) ($c['label'] ?? ''),
            'sort_order' => (int) ($c['sort_order'] ?? 0),
            'active'     => !empty($c['active']) ? _('Yes') : _('No'),
            'default'    => !empty($c['is_default']) ? _('Yes') : _('No'),
        ];
    }

    return new MasterSummaryTable(
        [
            ['key' => 'code', 'label' => _('Code')],
            ['key' => 'label', 'label' => _('Label')],
            ['key' => 'sort_order', 'label' => _('Sort')],
            ['key' => 'active', 'label' => _('Active')],
            ['key' => 'default', 'label' => _('Default')],
        ],
        $rows,
        ['edit' => true, 'delete' => true],
        [
            'record_id_field'      => 'id',
            'row_id_field'         => 'id',
            'tab_sel'              => 'conditions',
            'show_footer'          => false,
            'ajax'                 => false,
            'empty_message'        => _('No conditions defined yet.'),
            'delete_confirm_message' => _('Delete this condition? Products using it will fall back to the default.'),
        ]
    );
}

/**
 * Build the MasterSummaryTable for identifier lookups of the given type.
 *
 * @param array<int, array<string, mixed>> $entries Lookup rows
 * @param string                           $type    Lookup type ('brand' | 'manufacturer')
 * @return MasterSummaryTable
 *
 * @since 1.0.0
 */
function pa_lookups_summary(array $entries, string $type): MasterSummaryTable
{
    $rows = [];
    $n = 0;
    foreach ($entries as $e) {
        $n++;
        $rows[] = [
            'id'   => (int) ($e['id'] ?? 0),
            '#'    => $n,
            'name' => (string) ($e['name'] ?? ''),
        ];
    }

    return new MasterSummaryTable(
        [
            ['key' => '#', 'label' => '#'],
            ['key' => 'name', 'label' => _('Name')],
        ],
        $rows,
        ['edit' => true, 'delete' => true],
        [
            'record_id_field'      => 'id',
            'row_id_field'         => 'id',
            'tab_sel'              => $type,
            'show_footer'          => false,
            'ajax'                 => false,
            'empty_message'        => _('No entries defined yet.'),
            'delete_confirm_message' => _('Delete this entry?'),
        ]
    );
}

$editRowId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A MasterSummaryTable row-action POST carries the section selector in
    // _tabs_sel; prefer it so a row action returns to the same section.
    $postTabSel   = TabContext::fromPost($_POST, 'id')->getTabSel();
    $postSection  = $section;
    $postSub      = $sub;

    if (in_array($postTabSel, $attributeSubTabs, true)) {
        $postSection = 'attributes';
        $postSub = $postTabSel;
    } elseif ($postTabSel === 'assign_categories') {
        $postSection = 'attributes';
        $postSub = 'assign_categories';
    } elseif ($postTabSel === 'conditions') {
        $postSection = 'conditions';
    } elseif ($postTabSel === 'flags') {
        $postSection = 'flags';
    } elseif (isset($types[$postTabSel])) {
        $postSection = 'brands';
        $lookupType = $postTabSel;
    }

    if ($postSection === 'attributes') {
        $categoryId = (int) ($_GET['category_id'] ?? ($_POST['category_id'] ?? 0));
        $stockId    = trim((string) ($_GET['stock_id'] ?? ($_POST['stock_id'] ?? '')));

        $cats = $dao->listCategories();
        // Only the values sub-tab defaults the category selector (to the first
        // category); on assignments the Add Assignment box owns category choice,
        // so a new-assignment workflow starts with no category preselected.
        if ($categoryId === 0 && $postSub === 'values' && count($cats) > 0) {
            $categoryId = (int) $cats[0]['id'];
        }

        $rowAction = $postSub === 'assign_categories'
            ? pa_assigned_categories_summary($stockId, $dao)->getPostedAction($_POST)
            : pa_build_summary($postSub, $dao, $categoryId, $stockId)->getPostedAction($_POST);
        $action    = (string) ($_POST['action'] ?? '');

        if ($rowAction !== null) {
            $rowId = (int) $rowAction['id'];

            if ($rowAction['action'] === 'delete') {
                if ($postSub === 'assign_categories') {
                    if ($stockId !== '' && $rowId > 0) {
                        $dao->removeCategoryAssignment($stockId, $rowId);
                    }
                } else {
                    pa_delete_row($postSub, $dao, $rowId);
                }
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
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);

            // Multi-assign: union of ticked value checkboxes and the "Add All" flag.
            $valueIds = array_values(array_unique(array_map('intval', (array) ($_POST['value_ids'] ?? []))));
            if (!empty($_POST['add_all'])) {
                foreach ($dao->listValues($catId) as $v) {
                    $valueIds[] = (int) $v['id'];
                }
                $valueIds = array_values(array_unique($valueIds));
            }

            if ($sId !== '' && $catId > 0) {
                $dao->addCategoryAssignment($sId, $catId);
            }
            if ($sId !== '' && $catId > 0 && $valueIds !== []) {
                $pairs = [];
                foreach ($valueIds as $vid) {
                    if ($vid > 0) {
                        $pairs[] = ['category_id' => $catId, 'value_id' => $vid, 'sort_order' => $sortOrder];
                    }
                }
                $dao->assignValues($sId, $pairs);
            }

            header('Location: ' . pa_redirect_for('assignments', $catId, $sId));
            exit;
        }
    }

    if ($postSection === 'conditions') {
        $conditions = $dao->listConditions();
        $rowAction  = pa_conditions_summary($conditions)->getPostedAction($_POST);
        $action     = (string) ($_POST['action'] ?? '');

        if ($rowAction !== null) {
            $rowId = (int) $rowAction['id'];

            if ($rowAction['action'] === 'delete') {
                if ($rowId > 0) {
                    $dao->deleteCondition($rowId);
                }
                display_notification(_('Record deleted.'));

                // Re-query so the summary table no longer shows the deleted
                // condition in the same request (issue #57).
                $conditions = $dao->listConditions();
            } else {
                // Edit: fall through to render the form prefilled with this record.
                $editRowId = $rowId;
            }
        }

        if ($action === 'upsert_condition') {
            $conditionId = (int) ($_POST['id'] ?? 0);
            $dao->upsertCondition([
                'id'         => $conditionId > 0 ? $conditionId : null,
                'code'       => trim((string) ($_POST['code'] ?? '')),
                'label'      => trim((string) ($_POST['label'] ?? '')),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'active'     => isset($_POST['active']),
                'is_default' => isset($_POST['is_default']),
            ]);
            header('Location: ?tab=conditions');
            exit;
        }
    }

    if ($postSection === 'flags') {
        $flags = $flagsDao->listFlags();
        $rowAction = pa_flags_summary($flags)->getPostedAction($_POST);
        $action    = (string) ($_POST['action'] ?? '');

        if ($rowAction !== null) {
            $rowId = (int) $rowAction['id'];

            if ($rowAction['action'] === 'delete') {
                if ($rowId > 0) {
                    $flagsDao->deleteFlag($rowId);
                }
                display_notification(_('Record deleted.'));

                // Re-query so the summary table no longer shows the deleted flag
                // in the same request (issue #57).
                $flags = $flagsDao->listFlags();
            } else {
                // Edit: fall through to render the form prefilled with this record.
                $editRowId = $rowId;
            }
        }

        if ($action === 'add_flag') {
            $code      = trim((string) ($_POST['code'] ?? ''));
            $label     = trim((string) ($_POST['label'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $active    = isset($_POST['active']) ? 1 : 0;
            $flagId    = (int) ($_POST['flag_id'] ?? 0);

            if ($code !== '' && $label !== '') {
                $flagsDao->upsertFlag([
                    'id'         => $flagId > 0 ? $flagId : null,
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
            $flagId = (int) ($_POST['flag_id'] ?? 0);
            if ($flagId > 0) {
                $flagsDao->deleteFlag($flagId);
            }
            header('Location: ?tab=flags');
            exit;
        }
    }

    if ($postSection === 'brands') {
        $entries = $lookupsDao->listByType($lookupType);
        $rowAction = pa_lookups_summary($entries, $lookupType)->getPostedAction($_POST);
        $action    = (string) ($_POST['action'] ?? '');

        // The row-action form posts _tabs_sel = lookup type; use it so deletes
        // and edits return to the same type.
        $returnType = $lookupType;
        if (isset($types[$postTabSel])) {
            $returnType = $postTabSel;
        }

        if ($rowAction !== null) {
            $rowId = (int) $rowAction['id'];

            if ($rowAction['action'] === 'delete') {
                if ($rowId > 0) {
                    $lookupsDao->delete($rowId);
                }
                display_notification(_('Record deleted.'));

                // Re-query so the summary table no longer shows the deleted entry
                // in the same request (issue #57).
                $entries = $lookupsDao->listByType($lookupType);
            } else {
                // Edit: fall through to render the form prefilled with this record.
                $editRowId = $rowId;
            }
        }

        if ($action === 'add_entry') {
            $type = $_POST['entry_type'] ?? 'brand';
            $name = trim((string) ($_POST['name'] ?? ''));
            $entryId = (int) ($_POST['entry_id'] ?? 0);
            if ($name !== '' && isset($types[$type])) {
                if ($entryId > 0) {
                    $lookupsDao->update($entryId, $name);
                } else {
                    $lookupsDao->add($type, $name);
                }
            }
            header('Location: ?tab=brands&type=' . rawurlencode($returnType));
            exit;
        }

        if ($action === 'delete_entry') {
            $entryId = (int) ($_POST['entry_id'] ?? 0);
            if ($entryId > 0) {
                $lookupsDao->delete($entryId);
            }
            header('Location: ?tab=brands&type=' . rawurlencode($returnType));
            exit;
        }
    }
}

page(_('Product Attributes'), false, false, '', '');

echo '<h1>' . _('Product Attributes') . '</h1>';

// Section navigation.
$sectionLinks = [
    'attributes' => _('Attributes'),
    'conditions' => _('Conditions'),
    'flags'      => _('Lifecycle Flags'),
    'brands'     => _('Brands'),
];
echo '<nav>';
foreach ($sectionLinks as $key => $label) {
    $cls = $section === $key ? ' class="active"' : '';
    echo '<a href="?tab=' . $key . '"' . $cls . '>' . $label . '</a> &nbsp; ';
}
echo '</nav>';
echo '<br>';

if ($section === 'attributes'):

    // Sub-tabs of the Product Attributes taxonomy.
    $subLinks = [
        'categories'  => _('Categories'),
        'values'      => _('Values'),
        'assignments' => _('Assignments'),
    ];
    echo '<nav>';
    foreach ($subLinks as $key => $label) {
        $cls = $sub === $key ? ' class="active"' : '';
        echo '<a href="?tab=attributes&sub=' . $key . '"' . $cls . '>' . $label . '</a> &nbsp; ';
    }
    echo '</nav>';
    echo '<br>';

    $categoryId = (int) ($_GET['category_id'] ?? ($_POST['category_id'] ?? 0));
    $stockId    = trim((string) ($_GET['stock_id'] ?? ($_POST['stock_id'] ?? '')));

    $cats = $dao->listCategories();
    if ($categoryId === 0 && $sub === 'values' && count($cats) > 0) {
        $categoryId = (int) $cats[0]['id'];
    }

    if ($sub === 'categories'):
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
      <p class="royal-order-hint"><?php echo _('Sort orders follow the Royal Order of Adjectives: Quantity (1), Opinion (2), Size (3), Age (4), Shape (5), Color (6), Proper adjective (7), Material (8), Purpose (9).'); ?></p>
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
            <a href="?tab=attributes&sub=categories" style="margin-left:8px"><?php echo _('Cancel'); ?></a>
          <?php endif; ?>
        </div>
      </form>
    </fieldset>

    <?php elseif ($sub === 'values'):
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
      <input type="hidden" name="tab" value="attributes" />
      <input type="hidden" name="sub" value="values" />
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
            <a href="?tab=attributes&sub=values&category_id=<?= $categoryId ?>" style="margin-left:8px"><?php echo _('Cancel'); ?></a>
          <?php endif; ?>
        </div>
      </form>
    </fieldset>

    <?php else: /* assignments */
        $values = $categoryId ? $dao->listValues($categoryId) : [];
    ?>

    <h2><?php echo _('Assignments'); ?></h2>

    <form method="get">
      <input type="hidden" name="tab" value="attributes" />
      <input type="hidden" name="sub" value="assignments" />
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
      <div style="margin-top:8px"><button type="submit"><?php echo _('Load'); ?></button></div>
    </form>

    <?php if ($stockId !== ''): ?>
    <?php
        echo '<form method="post">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId, ENT_QUOTES, 'UTF-8') . '" />';
        pa_assigned_categories_summary($stockId, $dao)->render();
        echo '</form>';
    ?>
    <?php
        echo '<form method="post">';
        echo '<input type="hidden" name="category_id" value="' . htmlspecialchars((string)$categoryId, ENT_QUOTES, 'UTF-8') . '" />';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId, ENT_QUOTES, 'UTF-8') . '" />';
        pa_build_summary('assignments', $dao, $categoryId, $stockId)->render();
        echo '</form>';
    ?>

    <form method="post">
      <?php echo (new AddAssignmentForm($cats, $stockId, $categoryId, $values, 'admin_pa_', ['name' => '', 'value' => '', 'label' => 'Add'], ['action' => 'add_assignment']))->render(); ?>
    </form>

    <?php else: ?>
    <p><?php echo _('Enter a Stock ID to view/add assignments.'); ?></p>
    <?php endif; ?>

    <?php endif; ?>

<?php elseif ($section === 'conditions'):

    $conditions = $dao->listConditions();
    $editCondId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
    $editingCondition = null;
    foreach ($conditions as $c) {
        if ((int) ($c['id'] ?? 0) === $editCondId) {
            $editingCondition = $c;
            break;
        }
    }
    $formCode    = $editingCondition ? (string) ($editingCondition['code'] ?? '') : '';
    $formLabel   = $editingCondition ? (string) ($editingCondition['label'] ?? '') : '';
    $formSort    = $editingCondition ? (int) ($editingCondition['sort_order'] ?? 0) : 0;
    $formActive  = $editingCondition ? ((int) ($editingCondition['active'] ?? 1) === 1) : true;
    $formDefault = $editingCondition ? ((int) ($editingCondition['is_default'] ?? 0) === 1) : false;

    echo '<h2>' . _('Condition Definitions') . '</h2>';
    echo '<p>' . _('Manage the conditions shown as a single-choice radio box on the Product Attributes tab. Mark one value as the site default; it is preselected for products without their own condition.') . '</p>';

    echo '<form method="post">';
    pa_conditions_summary($conditions)->render();
    echo '</form>';

    echo '<fieldset>';
    echo '<legend>' . ($editingCondition ? _('Edit Condition') : _('Add Condition')) . '</legend>';
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="upsert_condition" />';
    if ($editingCondition) {
        echo '<input type="hidden" name="id" value="' . (int)$editingCondition['id'] . '" />';
    }
    echo '<div><label for="code">' . _('Code') . '</label>';
    echo '<input type="text" id="code" name="code" required placeholder="new" pattern="[a-z0-9_-]+" '
        . 'title="' . _('Letters, numbers, underscores and hyphens only') . '" value="' . htmlspecialchars($formCode, ENT_QUOTES, 'UTF-8') . '" /> ';
    echo '<small>' . _('Internal identifier (letters, numbers, underscores)') . '</small></div>';
    echo '<div><label for="label">' . _('Label') . '</label>';
    echo '<input type="text" id="label" name="label" required placeholder="New" '
        . 'value="' . htmlspecialchars($formLabel, ENT_QUOTES, 'UTF-8') . '" /> ';
    echo '<small>' . _('Display text in the Condition radio box') . '</small></div>';
    echo '<div><label for="sort_order">' . _('Sort Order') . '</label>';
    echo '<input type="number" id="sort_order" name="sort_order" value="' . $formSort . '" min="0" /></div>';
    echo '<div><label for="active">' . _('Active') . '</label>';
    echo '<input type="checkbox" id="active" name="active"' . ($formActive ? ' checked' : '') . ' />';
    echo '<small>' . _('Active conditions are selectable on products') . '</small></div>';
    echo '<div><label for="is_default">' . _('Default') . '</label>';
    echo '<input type="checkbox" id="is_default" name="is_default"' . ($formDefault ? ' checked' : '') . ' />';
    echo '<small>' . _('Site-wide default; unused products preselect this condition') . '</small></div>';
    echo '<div style="margin-top:8px"><button type="submit">' . _('Save Condition') . '</button>';
    if ($editingCondition) {
        echo ' <a href="?tab=conditions" style="margin-left:8px">' . _('Cancel') . '</a>';
    }
    echo '</div>';
    echo '</form>';
    echo '</fieldset>';

elseif ($section === 'flags'):

    $flags = $flagsDao->listFlags();
    $editFlagId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
    $editingFlag = null;
    foreach ($flags as $f) {
        if ((int) ($f['id'] ?? 0) === $editFlagId) {
            $editingFlag = $f;
            break;
        }
    }

    echo '<h2>' . _('Lifecycle Flag Definitions') . '</h2>';
    echo '<p>' . _('Manage the storefront flags that appear as checkboxes on the product lifecycle tab.') . '</p>';

    echo '<form method="post">';
    pa_flags_summary($flags)->render();
    echo '</form>';

    $formCode      = $editingFlag ? (string) ($editingFlag['code'] ?? '') : '';
    $formLabel     = $editingFlag ? (string) ($editingFlag['label'] ?? '') : '';
    $formSort      = $editingFlag ? (int) ($editingFlag['sort_order'] ?? 0) : 0;
    $formActive    = $editingFlag ? ((int) ($editingFlag['active'] ?? 1) === 1) : true;

    echo '<fieldset>';
    echo '<legend>' . ($editingFlag ? _('Edit Flag') : _('Add Flag')) . '</legend>';
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="add_flag" />';
    if ($editingFlag) {
        echo '<input type="hidden" name="flag_id" value="' . (int)$editingFlag['id'] . '" />';
    }
    echo '<div><label for="code">' . _('Code') . '</label>';
    echo '<input type="text" id="code" name="code" required placeholder="is_organic" pattern="[a-z0-9_-]+" '
        . 'title="' . _('Letters, numbers, underscores and hyphens only') . '" value="' . htmlspecialchars($formCode, ENT_QUOTES, 'UTF-8') . '" /> ';
    echo '<small>' . _('Internal identifier (letters, numbers, underscores)') . '</small></div>';
    echo '<div><label for="label">' . _('Label') . '</label>';
    echo '<input type="text" id="label" name="label" required placeholder="Organic Certified" '
        . 'value="' . htmlspecialchars($formLabel, ENT_QUOTES, 'UTF-8') . '" /> ';
    echo '<small>' . _('Display text on the lifecycle tab') . '</small></div>';
    echo '<div><label for="sort_order">' . _('Sort Order') . '</label>';
    echo '<input type="number" id="sort_order" name="sort_order" value="' . $formSort . '" min="0" /></div>';
    echo '<div><label for="active">' . _('Active') . '</label>';
    echo '<input type="checkbox" id="active" name="active"' . ($formActive ? ' checked' : '') . ' /></div>';
    echo '<div style="margin-top:8px"><button type="submit">' . _('Save Flag') . '</button>';
    if ($editingFlag) {
        echo ' <a href="?tab=flags" style="margin-left:8px">' . _('Cancel') . '</a>';
    }
    echo '</div>';
    echo '</form>';
    echo '</fieldset>';

else: /* brands */

    $entries = $lookupsDao->listByType($lookupType);
    $editEntryId = $editRowId ?: (int) ($_GET['edit_id'] ?? 0);
    $editingEntry = null;
    foreach ($entries as $e) {
        if ((int) ($e['id'] ?? 0) === $editEntryId) {
            $editingEntry = $e;
            break;
        }
    }

    echo '<h2>' . _('Brand / Manufacturer Management') . '</h2>';
    echo '<p>' . _('Manage the dropdown values that appear in the Product Identifiers tab.') . '</p>';

    echo '<div class="nav">';
    foreach ($types as $key => $label) {
        $cls = $key === $lookupType ? 'active' : '';
        echo '<a href="?tab=brands&type=' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a> &nbsp; ';
    }
    echo '</div>';
    echo '<br>';

    echo '<form method="post">';
    pa_lookups_summary($entries, $lookupType)->render();
    echo '</form>';

    echo '<fieldset>';
    echo '<legend>' . ($editingEntry ? _('Edit') : _('Add')) . ' ' . htmlspecialchars($types[$lookupType], ENT_QUOTES, 'UTF-8') . '</legend>';
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="add_entry" />';
    echo '<input type="hidden" name="entry_type" value="' . htmlspecialchars($lookupType, ENT_QUOTES, 'UTF-8') . '" />';
    if ($editingEntry) {
        echo '<input type="hidden" name="entry_id" value="' . (int)$editingEntry['id'] . '" />';
    }
    echo '<div><label for="name">' . _('Name') . '</label>';
    echo '<input type="text" id="name" name="name" required maxlength="128" '
        . 'value="' . htmlspecialchars((string)($editingEntry['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '" /></div>';
    echo '<div style="margin-top:8px"><button type="submit">' . ($editingEntry ? _('Save') : _('Add')) . ' '
        . htmlspecialchars($types[$lookupType], ENT_QUOTES, 'UTF-8') . '</button>';
    if ($editingEntry) {
        echo ' <a href="?tab=brands&type=' . htmlspecialchars($lookupType, ENT_QUOTES, 'UTF-8') . '" style="margin-left:8px">' . _('Cancel') . '</a>';
    }
    echo '</div>';
    echo '</form>';
    echo '</fieldset>';

endif;

end_page();