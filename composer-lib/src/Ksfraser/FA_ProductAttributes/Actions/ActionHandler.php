<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\FrontAccountingDbAdapter;

class ActionHandler
{
    /** @var ProductAttributesDao */
    private $dao;
    /** @var FrontAccountingDbAdapter */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, FrontAccountingDbAdapter $dbAdapter)
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
                    $handler = new DeleteValueAction($this->dao);
                    return $handler->handle($postData);

                case 'add_assignment':
                    $handler = new AddAssignmentAction($this->dao);
                    return $handler->handle($postData);

                case 'delete_assignment':
                    $handler = new DeleteAssignmentAction($this->dao);
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