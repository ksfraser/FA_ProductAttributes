<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Actions\CreateChildProductAction;
use Ksfraser\FA_ProductAttributes\Actions\GenerateCombosAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\CombosDao;
use Ksfraser\FA_ProductAttributes\Variations\UI\AssignedCategoriesSection;
use Ksfraser\FA_ProductAttributes\Variations\UI\CombinationPoolSection;
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
        $this->handlePostActions($stockId);

        $assignedCategories = ($stockId !== '') ? $this->coreDao->listCategoriesWithAssignedValues($stockId) : [];
        $parentData = $this->resolveParentData($stockId);
        // A child product is a variation of a parent: its category
        // assignments are managed on the parent and shown here read-only.
        $isChild = !empty($parentData);
        $variations = ($stockId !== '') ? $this->dao->getProductVariations($stockId) : [];
        $assignments = ($stockId !== '') ? $this->coreDao->listAssignments($stockId) : [];

        (new ParentProductSection())->render($parentData);

        $categories = new AssignedCategoriesSection($this->coreDao);
        $categories->render($stockId, $assignedCategories, $isChild);

        (new CurrentAssignmentsSection())->render($assignments, !empty($assignedCategories));

        (new ExistingVariationsSection())->render($variations);

        // Show the persisted combination pool so Generate's result is visible
        // even before Create Child instantiates any children.
        $combinationPool = [];
        if ($stockId !== '' && !$isChild) {
            $combinationPool = (new CombosDao($this->db))->listCombos($stockId);
            (new CombinationPoolSection())->render($combinationPool);
        }

        $buttons = new VariationActionButtons();
        // Create Child must stay inactive until a combination set exists (#62).
        $buttons->render($stockId !== '' && !$isChild, !empty($combinationPool));
    }

    public function handleSave(string $stockId, array $postData): void
    {
        // Variations don't have a simple "save" — actions are dispatched via POST
    }

    public function handleDelete(string $stockId): void
    {
        $this->dao->clearParentRelationship($stockId);
        $this->coreDao->setProductParent($stockId, null);
        // Purge the parent's combination pool (issue #53). Guarded: no child
        // product may remain when an item delete reaches here.
        (new CombosDao($this->db))->deleteParentPool($stockId);
    }

    /**
     * Refuse to delete a product that still has variation children (issue #53).
     *
     * Called from the pre_item_delete hook BEFORE any tab cleanup runs, so
     * deleting a parent product with children is blocked outright and the
     * children cannot be orphaned.
     *
     * @throws \RuntimeException when the product has children.
     */
    public function assertDeletable(string $stockId): void
    {
        if ($stockId === '') {
            return;
        }

        $combosDao = new CombosDao($this->db);
        $children = array_values(array_unique(array_merge(
            $combosDao->listChildrenByParent($stockId),
            $combosDao->listPoolChildStockIds($stockId)
        )));

        if (!empty($children)) {
            throw new \RuntimeException(sprintf(
                _("Cannot delete '%s': it is a parent product with %d variation child(ren) (%s). Delete the children first."),
                $stockId,
                count($children),
                implode(', ', $children)
            ));
        }
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

        global $Ajax;
        $Ajax->activate('tabs');

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
    }
}
