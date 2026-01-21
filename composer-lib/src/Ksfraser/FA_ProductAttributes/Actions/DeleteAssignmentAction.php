<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\AssignmentService;

class DeleteAssignmentAction
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
            $assignmentId = (int)($postData['assignment_id'] ?? 0);

            $this->assignmentService->deleteAssignment($assignmentId);

            return _("Assignment removed successfully");
        } catch (\Exception $e) {
            display_error("Error deleting assignment: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}