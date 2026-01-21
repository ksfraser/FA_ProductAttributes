<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

class DeleteAssignmentAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    public function handle(array $postData): string
    {
        try {
            $assignmentId = (int)($postData['assignment_id'] ?? 0);
            $stockId = trim((string)($postData['stock_id'] ?? ''));

            display_notification("DeleteAssignmentAction: assignment_id=$assignmentId, stock_id='$stockId'");

            if ($assignmentId <= 0) {
                throw new \Exception("Assignment ID is required");
            }

            $this->dao->deleteAssignment($assignmentId);

            return _("Assignment removed successfully");
        } catch (\Exception $e) {
            display_error("Error deleting assignment: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}