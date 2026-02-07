<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes_Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class ActionHandler
{
    /** @var VariationsDao */
    private $variationsDao;
    /** @var ProductAttributesDao */
    private $productAttributesDao;
    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(VariationsDao $variationsDao, ProductAttributesDao $productAttributesDao, DbAdapterInterface $dbAdapter)
    {
        $this->variationsDao = $variationsDao;
        $this->productAttributesDao = $productAttributesDao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(string $action, array $postData): ?string
    {
        try {
            switch ($action) {
                case 'upsert_category':
                    $handler = new UpsertCategoryAction($this->variationsDao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'delete_category':
                    $handler = new DeleteCategoryAction($this->variationsDao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'upsert_value':
                    $handler = new UpsertValueAction($this->variationsDao);
                    return $handler->handle($postData);

                case 'delete_value':
                    $handler = new DeleteValueAction($this->variationsDao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'add_assignment':
                    $handler = new AddAssignmentAction($this->productAttributesDao);
                    return $handler->handle($postData);

                case 'delete_assignment':
                    $handler = new DeleteAssignmentAction($this->productAttributesDao);
                    return $handler->handle($postData);

                case 'add_category_assignment':
                    $handler = new AddCategoryAssignmentAction($this->productAttributesDao);
                    return $handler->handle($postData);

                case 'remove_category_assignment':
                    $handler = new RemoveCategoryAssignmentAction($this->productAttributesDao);
                    return $handler->handle($postData);

                case 'update_category_assignments':
                    $handler = new UpdateCategoryAssignmentsAction($this->productAttributesDao);
                    return $handler->handle($postData);

                default:
                    return null;
            }
        } catch (\Exception $e) {
            display_error("Error handling action '$action': " . $e->getMessage());
            return null;
        }
    }
}