<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\ValueService;

class DeleteValueAction
{
    /** @var ValueService */
    private $valueService;

    public function __construct(ValueService $valueService)
    {
        $this->valueService = $valueService;
    }

    public function handle(array $postData): string
    {
        try {
            $valueId = (int)($postData['value_id'] ?? 0);

            $result = $this->valueService->deleteValue($valueId);

            if ($result['hard_delete']) {
                return _("Value deleted successfully");
            } else {
                return _("Value deactivated successfully (in use by products)");
            }
        } catch (\Exception $e) {
            display_error("Error deleting value: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}