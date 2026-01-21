<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\CategoryService;
use Ksfraser\FA_ProductAttributes\Services\ValueService;
use Ksfraser\FA_ProductAttributes\Services\AssignmentService;

class ActionHandler
{
    /** @var CategoryService */
    private $categoryService;

    /** @var ValueService */
    private $valueService;

    /** @var AssignmentService */
    private $assignmentService;

    public function __construct(CategoryService $categoryService, ValueService $valueService, AssignmentService $assignmentService)
    {
        $this->categoryService = $categoryService;
        $this->valueService = $valueService;
        $this->assignmentService = $assignmentService;
    }

    public function handle(string $action, array $postData): ?string
    {
        try {
            switch ($action) {
                case 'upsert_category':
                    $handler = new UpsertCategoryAction($this->categoryService);
                    return $handler->handle($postData);

                case 'delete_category':
                    $handler = new DeleteCategoryAction($this->categoryService);
                    return $handler->handle($postData);

                case 'upsert_value':
                    $handler = new UpsertValueAction($this->valueService);
                    return $handler->handle($postData);

                case 'delete_value':
                    $handler = new DeleteValueAction($this->valueService);
                    return $handler->handle($postData);

                case 'add_assignment':
                    $handler = new AddAssignmentAction($this->assignmentService);
                    return $handler->handle($postData);

                case 'delete_assignment':
                    $handler = new DeleteAssignmentAction($this->assignmentService);
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