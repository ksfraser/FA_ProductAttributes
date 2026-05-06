<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

/**
 * Single Responsibility: Deletes a single attribute assignment by ID.
 */
class DeleteAssignmentAction
{
    /** @var ProductAttributesDao */
    private $dao;

    public function __construct(ProductAttributesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     * @throws \Exception if assignment_id is invalid
     */
    public function handle(array $postData): string
    {
        $assignmentId = (int)($postData['assignment_id'] ?? 0);

        if ($assignmentId <= 0) {
            throw new \Exception("Assignment ID is required");
        }

        $this->dao->deleteAssignment($assignmentId);

        return "Assignment removed successfully";
    }
}
