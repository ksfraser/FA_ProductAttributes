<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildProductAction;
use Ksfraser\FA_ProductAttributes\Actions\GenerateCombosAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\FA_ProductAttributes\Variations\UI\AssignedCategoriesSection;
use Ksfraser\FA_ProductAttributes\Variations\UI\CurrentAssignmentsSection;
use Ksfraser\FA_ProductAttributes\Variations\UI\ExistingVariationsSection;
use Ksfraser\FA_ProductAttributes\Variations\UI\ParentProductSection;
use Ksfraser\FA_ProductAttributes\Variations\UI\VariationActionButtons;
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
        echo "<!-- PA_DEBUG: renderTabContent ENTER stockId=" . htmlspecialchars($stockId) . " -->\n";
        $this->handlePostActions($stockId);
        echo "<!-- PA_DEBUG: handlePostActions DONE -->\n";

        $assignedCategories = ($stockId !== '') ? $this->dao->listCategoryAssignments($stockId) : [];
        $allCategories = $this->dao->listCategories();
        $parentData = $this->resolveParentData($stockId);
        // A child product is a variation of a parent: its category
        // assignments are managed on the parent and shown here read-only.
        $isChild = !empty($parentData);
        $variations = ($stockId !== '') ? $this->dao->getProductVariations($stockId) : [];
        $assignments = ($stockId !== '') ? $this->coreDao->listAssignments($stockId) : [];
        echo "<!-- PA_DEBUG: data fetched assignedCategories=" . count($assignedCategories) . " variations=" . count($variations) . " assignments=" . count($assignments) . " -->\n";

        (new ParentProductSection())->render($parentData);
        echo "<!-- PA_DEBUG: ParentProductSection rendered -->\n";

        $categories = new AssignedCategoriesSection($this->coreDao);
        $categories->render($stockId, $assignedCategories, $allCategories, $isChild);
        echo "<!-- PA_DEBUG: AssignedCategoriesSection rendered -->\n";

        (new CurrentAssignmentsSection())->render($assignments, !empty($assignedCategories));
        echo "<!-- PA_DEBUG: CurrentAssignmentsSection rendered -->\n";

        (new ExistingVariationsSection())->render($variations);
        echo "<!-- PA_DEBUG: ExistingVariationsSection rendered -->\n";

        $buttons = new VariationActionButtons();
        $buttons->render($stockId !== '' && !$isChild);
        echo "<!-- PA_DEBUG: VariationActionButtons rendered -->\n";
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
        echo "<!-- PA_DEBUG: handlePostActions ENTER method=" . ($_SERVER['REQUEST_METHOD'] ?? '?') . " -->\n";
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            echo "<!-- PA_DEBUG: handlePostActions early return (not POST or empty stockId) -->\n";
            return;
        }

        echo "<!-- PA_DEBUG: handlePostActions activating tabs -->\n";
        global $Ajax;
        $Ajax->activate('tabs');
        echo "<!-- PA_DEBUG: handlePostActions tabs activated -->\n";

        if (isset($_POST['assign_category_submit'])) {
            echo "<!-- PA_DEBUG: handlePostActions assign_category_submit branch -->\n";
            $categoryId = (int)($_POST['assign_category_id'] ?? 0);
            if ($categoryId > 0) {
                $this->coreDao->addCategoryAssignment($stockId, $categoryId);
                display_notification(_("Category assigned"));
            }
            return;
        }

        if (isset($_POST['unassign_category_submit'])) {
            echo "<!-- PA_DEBUG: handlePostActions unassign_category_submit branch -->\n";
            $categoryId = (int)($_POST['unassign_category_id'] ?? 0);
            if ($categoryId > 0) {
                $this->coreDao->removeCategoryAssignment($stockId, $categoryId);
                display_notification(_("Category unassigned"));
            }
            return;
        }

if (isset($_POST['generate_combos'])) {
            try {
                $combosDao = new CombosDao($this->db);
                $action = new GenerateCombosAction($this->coreDao, $combosDao, $this->db);
                $message = $action->handle($_POST);
                display_notification($message);
            } catch (\Throwable $e) {
                display_error($e->getMessage());
            }
            return;
        }

        if (isset($_POST['create_child_product'])) {
            $combosDao = new CombosDao($this->db);
            try {
                $action = new CreateChildProductAction($this->dao, $this->coreDao, $combosDao, $this->db);
                $message = $action->handle($_POST);
                display_notification($message);
            } catch (\InvalidArgumentException $e) {
                display_error($e->getMessage());
            } catch (\Throwable $e) {
                display_error($e->getMessage());
            }
            return;
        }
        echo "<!-- PA_DEBUG: handlePostActions no action matched -->\n";
    }
}