<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\AssignmentService;

class AddAssignmentAction
{
    /** @var AssignmentService */
    private $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function handle(array $postData): string
    {
        try {
            $data = [
                'stock_id' => trim((string)($postData['stock_id'] ?? '')),
                'category_id' => (int)($postData['category_id'] ?? 0),
                'value_id' => (int)($postData['value_id'] ?? 0),
                'sort_order' => (int)($postData['sort_order'] ?? 0)
            ];

            $this->assignmentService->createAssignment($data);
            return _("Added assignment");
        } catch (\Exception $e) {
            display_error("Error adding assignment: " . $e->getMessage());
            return _("Invalid assignment data");
        }
    }
}