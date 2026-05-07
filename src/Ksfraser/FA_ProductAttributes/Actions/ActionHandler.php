<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMediaDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Actions\CloneShippingToVariationsAction;
use Ksfraser\FA_ProductAttributes\Variations\Actions\AssignParentAction;
use Ksfraser\FA_ProductAttributes\Variations\Actions\CreateMissingVariationsAction;
use Ksfraser\FA_ProductAttributes\Variations\Actions\MakeInactiveAction;
use Ksfraser\FA_ProductAttributes\Variations\Actions\ReactivateVariationsAction;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Dispatches form POST actions to the correct Action class.
 *
 * Maps action name strings → action handler instances.
 */
class ActionHandler
{
    /** @var VariationsDao */
    private $variationsDao;

    /** @var ProductAttributesDao */
    private $productAttributesDao;

    /** @var ShippingAttributesDao */
    private $shippingAttributesDao;

    /** @var ProductIdentifiersDao|null */
    private $identifiersDao;

    /** @var ProductLifecycleDao|null */
    private $lifecycleDao;

    /** @var ProductTagsDao|null */
    private $tagsDao;

    /** @var ProductMediaDao|null */
    private $mediaDao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(
        VariationsDao        $variationsDao,
        ProductAttributesDao $productAttributesDao,
        DbAdapterInterface   $dbAdapter,
        ?ShippingAttributesDao  $shippingAttributesDao = null,
        ?ProductIdentifiersDao  $identifiersDao        = null,
        ?ProductLifecycleDao    $lifecycleDao          = null,
        ?ProductTagsDao         $tagsDao               = null,
        ?ProductMediaDao        $mediaDao              = null
    ) {
        $this->variationsDao         = $variationsDao;
        $this->productAttributesDao  = $productAttributesDao;
        $this->dbAdapter             = $dbAdapter;
        $this->shippingAttributesDao = $shippingAttributesDao;
        $this->identifiersDao        = $identifiersDao;
        $this->lifecycleDao          = $lifecycleDao;
        $this->tagsDao               = $tagsDao;
        $this->mediaDao              = $mediaDao;
    }

    /**
     * Dispatch an action by name.
     *
     * @param string              $action   The action identifier (e.g. 'upsert_category')
     * @param array<string, mixed> $postData POST data from the request
     * @return string|null Result message, or null if action is unknown or threw an exception
     */
    public function handle(string $action, array $postData): ?string
    {
        try {
            switch ($action) {
                case 'upsert_category':
                    return (new UpsertCategoryAction($this->variationsDao, $this->dbAdapter))->handle($postData);

                case 'delete_category':
                    return (new DeleteCategoryAction($this->variationsDao, $this->dbAdapter))->handle($postData);

                case 'add_assignment':
                    return (new AddAssignmentAction($this->productAttributesDao))->handle($postData);

                case 'delete_assignment':
                    return (new DeleteAssignmentAction($this->productAttributesDao))->handle($postData);

                case 'add_category_assignment':
                    return (new AddCategoryAssignmentAction($this->productAttributesDao))->handle($postData);

                case 'remove_category_assignment':
                    return (new RemoveCategoryAssignmentAction($this->productAttributesDao))->handle($postData);

                case 'update_category_assignments':
                    return (new UpdateCategoryAssignmentsAction($this->productAttributesDao))->handle($postData);

                case 'upsert_value':
                    return (new UpsertValueAction($this->productAttributesDao))->handle($postData);

                case 'delete_value':
                    return (new DeleteValueAction($this->variationsDao, $this->dbAdapter))->handle($postData);

                case 'generate_variations':
                    return (new GenerateVariationsAction($this->productAttributesDao, $this->dbAdapter, $this->shippingAttributesDao))->handle($postData);

                case 'create_child':
                    return (new CreateChildAction($this->productAttributesDao, $this->dbAdapter))->handle($postData);

                case 'update_product_types':
                    return (new UpdateProductTypesAction($this->productAttributesDao, $this->dbAdapter))->handle($postData);

                case 'make_inactive':
                    return (new MakeInactiveAction($this->variationsDao, $this->dbAdapter))->handle($postData);

                case 'reactivate_variations':
                    return (new ReactivateVariationsAction($this->variationsDao, $this->dbAdapter))->handle($postData);

                case 'create_missing_variations':
                    return (new CreateMissingVariationsAction($this->variationsDao, $this->productAttributesDao, $this->dbAdapter, $this->shippingAttributesDao))->handle($postData);

                case 'assign_parent':
                    return (new AssignParentAction($this->variationsDao, $this->dbAdapter))->handle($postData);

                case 'upsert_shipping_attributes':
                    if ($this->shippingAttributesDao !== null) {
                        return (new UpsertShippingAttributesAction($this->shippingAttributesDao))->handle($postData);
                    }
                    return null;

                case 'clone_shipping_to_variations':
                    if ($this->shippingAttributesDao !== null) {
                        return (new CloneShippingToVariationsAction($this->shippingAttributesDao))->handle($postData);
                    }
                    return null;

                case 'upsert_identifiers':
                    if ($this->identifiersDao !== null) {
                        return (new UpsertProductIdentifiersAction($this->identifiersDao))->handle($postData);
                    }
                    return null;

                case 'clone_identifiers_to_variations':
                    if ($this->identifiersDao !== null) {
                        return (new CloneIdentifiersToVariationsAction($this->identifiersDao))->handle($postData);
                    }
                    return null;

                case 'upsert_lifecycle':
                    if ($this->lifecycleDao !== null) {
                        return (new UpsertProductLifecycleAction($this->lifecycleDao))->handle($postData);
                    }
                    return null;

                case 'clone_lifecycle_to_variations':
                    if ($this->lifecycleDao !== null) {
                        return (new CloneLifecycleToVariationsAction($this->lifecycleDao))->handle($postData);
                    }
                    return null;

                case 'upsert_tag':
                    if ($this->tagsDao !== null) {
                        return (new UpsertTagAction($this->tagsDao))->handle($postData);
                    }
                    return null;

                case 'delete_tag':
                    if ($this->tagsDao !== null) {
                        return (new DeleteTagAction($this->tagsDao))->handle($postData);
                    }
                    return null;

                case 'add_tag_assignment':
                    if ($this->tagsDao !== null) {
                        return (new AddTagAssignmentAction($this->tagsDao))->handle($postData);
                    }
                    return null;

                case 'remove_tag_assignment':
                    if ($this->tagsDao !== null) {
                        return (new RemoveTagAssignmentAction($this->tagsDao))->handle($postData);
                    }
                    return null;

                case 'add_product_media':
                    if ($this->mediaDao !== null) {
                        return (new AddProductMediaAction($this->mediaDao))->handle($postData);
                    }
                    return null;

                case 'delete_product_media':
                    if ($this->mediaDao !== null) {
                        return (new DeleteProductMediaAction($this->mediaDao))->handle($postData);
                    }
                    return null;

                case 'set_media_variation_links':
                    if ($this->mediaDao !== null) {
                        return (new SetMediaVariationLinksAction($this->mediaDao))->handle($postData);
                    }
                    return null;

                default:
                    return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
