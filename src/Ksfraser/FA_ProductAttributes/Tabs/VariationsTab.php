<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use FrontAccounting\ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildAction;
use Ksfraser\FA_ProductAttributes\Actions\GenerateVariationsAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class VariationsTab extends AbstractTab
{
    /** @var VariationsDao */
    private $dao;

    /** @var ProductAttributesDao */
    private $coreDao;

    /** @var DbAdapterInterface */
    private $db;

    public function __construct(VariationsDao $dao, ProductAttributesDao $coreDao, DbAdapterInterface $db)
    {
        $this->dao     = $dao;
        $this->coreDao = $coreDao;
        $this->db      = $db;
    }

    public function getName(): string
    {
        return 'product_variations';
    }

    public function getTabKey(): string
    {
        return 'product_variations';
    }

    public function getTabLabel(): string
    {
        return _('Variations');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $assignedCategories = ($stockId !== '') ? $this->dao->listCategoryAssignments($stockId) : [];
        $allCategories = $this->dao->listCategories();
        $parentData = $this->resolveParentData($stockId);
        // A child product is a variation of a parent: its category
        // assignments are managed on the parent and shown here read-only.
        $isChild = !empty($parentData);
        $variations = ($stockId !== '') ? $this->dao->getProductVariations($stockId) : [];

        if ($parentData) {
            echo '<fieldset><legend>' . _('Parent Product') . '</legend>';
            echo '<p>' . _('This product is a variation of') . ' <strong>'
                . htmlspecialchars($parentData['stock_id']) . '</strong> — '
                . htmlspecialchars($parentData['description'] ?? '') . '</p>';
            echo '</fieldset>';
        }

        echo '<fieldset><legend>' . _('Assigned Categories') . '</legend>';
        if (empty($assignedCategories)) {
            if ($isChild) {
                echo '<p>' . _('No categories assigned. Categories are managed on the parent product.') . '</p>';
            } else {
                echo '<p>' . _('No categories assigned. Use the dropdown below to assign one.') . '</p>';
            }
        } else {
            $isReadOnly = $isChild;
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Active Values') . '</th><th>' . _('Actions') . '</th></tr>';
            foreach ($assignedCategories as $category) {
                $activeValues = $this->coreDao->listActiveValues((int)$category['id']);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($category['label']) . '</td>';
                echo '<td>' . count($activeValues) . ' ' . _('values') . '</td>';
                echo '<td>';
                if (!$isReadOnly) {
                    echo '<a href="' . $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/index.php?tab=values&category_id='
                        . $category['id'] . '">' . _('Manage Values') . '</a> ';
                    if ($stockId !== '') {
                        echo '<input type="hidden" name="unassign_category_id" value="' . (int)$category['id'] . '">';
                        echo '<input type="submit" name="unassign_category_submit" value="' . htmlspecialchars(_('Remove'), ENT_QUOTES) . '"'
                            . ' onclick="return confirm(\'' . htmlspecialchars(_('Remove this category from the product?'), ENT_QUOTES) . '\')">';
                    }
                } else {
                    echo '<span style="color:#666">' . _('inherited from parent') . '</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        $assignments = ($stockId !== '') ? $this->coreDao->listAssignments($stockId) : [];
        if (!empty($assignments)) {
            echo '<h5>' . _('Current Attribute Assignments') . '</h5>';
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Category') . '</th><th>' . _('Value') . '</th><th>' . _('Sort') . '</th></tr>';
            foreach ($assignments as $a) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)($a['category_label'] ?? '')) . '</td>';
                echo '<td>' . htmlspecialchars((string)($a['value_label'] ?? '')) . '</td>';
                echo '<td>' . (int)($a['sort_order'] ?? 0) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<p style="color:#666;font-size:11px">'
                . _('These attribute values determine the variations that will be generated.')
                . '</p>';
        } else {
            if (!empty($assignedCategories)) {
                echo '<p style="color:#666;font-size:11px">'
                    . _('Categories are assigned but no specific values are set. ')
                    . _('Use the <strong>Product Attributes</strong> tab to assign values, or the')
                    . ' <a href="' . $GLOBALS['path_to_root'] . '/modules/FA_ProductAttributes/public/index.php">'
                    . _('admin page') . '</a> '
                    . _('to manage them.')
                    . '</p>';
            }
        }

        if ($stockId !== '' && !$isChild && !empty($allCategories)) {
            $assignedIds = array_column($assignedCategories, 'id');
            $unassigned = array_filter($allCategories, function ($cat) use ($assignedIds) {
                return !in_array($cat['id'], $assignedIds, true);
            });
            if (!empty($unassigned)) {
                echo '<div style="margin-top:8px">';
                echo '<select name="assign_category_id">';
                echo '<option value="0">' . _('-- Select category --') . '</option>';
                foreach ($unassigned as $cat) {
                    echo '<option value="' . (int)$cat['id'] . '">' . htmlspecialchars($cat['label']) . '</option>';
                }
                echo '</select> ';
                echo '<input type="submit" name="assign_category_submit" value="' . _('Assign Category') . '">';
                echo '</div>';
            }
        }
        echo '</fieldset>';

        if (!empty($variations)) {
            echo '<fieldset><legend>' . _('Existing Variations') . '</legend>';
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('Stock ID') . '</th><th>' . _('Description') . '</th></tr>';
            foreach ($variations as $v) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($v['stock_id']) . '</td>';
                echo '<td>' . htmlspecialchars($v['description'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</fieldset>';
        }

        if ($stockId !== '' && !$isChild) {
            echo '<p><input type="submit" name="generate_variations" value="' . _('Generate Variations') . '"> ';
            echo '<input type="submit" name="create_child" value="' . _('Create Child Product') . '">';
            echo '</p>';
        }
    }

    public function handleSave(string $stockId, array $postData): void
    {
        // Variations don't have a simple "save" — actions are dispatched via POST
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->clearParentRelationship($stockId);
        $this->coreDao->setProductParent($stockId, null);
    }

    /**
     * Resolve the parent product for a stock id, or null if the product is not
     * a child (variation) of another product.
     *
     * The canonical product_hierarchy table is checked first, then the legacy
     * parent link recorded on product_attribute_assignments rows.
     */
    private function resolveParentData(string $stockId): ?array
    {
        if ($stockId === '') {
            return null;
        }

        $parentId = $this->coreDao->getProductParent($stockId);
        if ($parentId !== null && $parentId !== '') {
            $details = $this->dao->getParentProductData($parentId);

            return [
                'stock_id'    => $parentId,
                'description' => $details['description'] ?? '',
            ];
        }

        return $this->dao->getProductParent($stockId);
    }

    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }

        if (isset($_POST['assign_category_submit'])) {
            $categoryId = (int)($_POST['assign_category_id'] ?? 0);
            if ($categoryId > 0) {
                $this->coreDao->addCategoryAssignment($stockId, $categoryId);
                display_notification(_("Category assigned"));
            }
            return;
        }

        if (isset($_POST['unassign_category_submit'])) {
            $categoryId = (int)($_POST['unassign_category_id'] ?? 0);
            if ($categoryId > 0) {
                $this->coreDao->removeCategoryAssignment($stockId, $categoryId);
                display_notification(_("Category unassigned"));
            }
            return;
        }

        if (isset($_POST['generate_variations'])) {
            $action = new GenerateVariationsAction($this->coreDao, $this->db);
            $message = $action->handle($_POST);
            display_notification($message);
            return;
        }

        if (isset($_POST['create_child'])) {
            $action = new CreateChildAction($this->dao, $this->coreDao, $this->db);
            try {
                $message = $action->handle($_POST);
                display_notification($message);
            } catch (\InvalidArgumentException $e) {
                display_error($e->getMessage());
            }
            return;
        }
    }
}
