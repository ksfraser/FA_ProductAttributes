<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\CategoryService;

class DeleteCategoryAction
{
    /** @var CategoryService */
    private $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function handle(array $postData): string
    {
        try {
            $categoryId = (int)($postData['category_id'] ?? 0);

            $result = $this->categoryService->deleteCategory($categoryId);

            if ($result['hard_delete']) {
                return _("Category deleted successfully");
            } else {
                return _("Category deactivated successfully (in use by products)");
            }
        } catch (\Exception $e) {
            display_error("Error deleting category: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}