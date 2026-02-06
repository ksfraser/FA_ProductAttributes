<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class ActionHandler
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    public function handle(string $action, array $postData): ?string
    {
        try {
            switch ($action) {
                case 'upsert_category':
                    $handler = new UpsertCategoryAction($this->dao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'delete_category':
                    $handler = new DeleteCategoryAction($this->dao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'upsert_value':
                    $handler = new UpsertValueAction($this->dao);
                    return $handler->handle($postData);

                case 'delete_value':
                    $handler = new DeleteValueAction($this->dao, $this->dbAdapter);
                    return $handler->handle($postData);

                case 'add_assignment':
                    $handler = new AddAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'delete_assignment':
                    $handler = new DeleteAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'add_category_assignment':
                    $handler = new AddCategoryAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'remove_category_assignment':
                    $handler = new RemoveCategoryAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'update_category_assignments':
                    $handler = new UpdateCategoryAssignmentsAction($this->dao);
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