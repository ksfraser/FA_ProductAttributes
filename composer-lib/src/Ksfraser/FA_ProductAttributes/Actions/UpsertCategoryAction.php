<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\CategoryService;

class UpsertCategoryAction
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

            $data = [
                'code' => trim((string)($postData['code'] ?? '')),
                'label' => trim((string)($postData['label'] ?? '')),
                'description' => trim((string)($postData['description'] ?? '')),
                'sort_order' => (int)($postData['sort_order'] ?? 0),
                'active' => isset($postData['active'])
            ];

            if ($categoryId > 0) {
                $this->categoryService->updateCategory($categoryId, $data);
                return _("Category updated successfully");
            } else {
                $this->categoryService->createCategory($data);
                return _("Category saved successfully");
            }
        } catch (\Exception $e) {
            display_error("Error saving category: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}