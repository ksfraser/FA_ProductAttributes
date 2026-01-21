<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Services\ValueService;

class UpsertValueAction
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

            $data = [
                'category_id' => (int)($postData['category_id'] ?? 0),
                'value' => trim((string)($postData['value'] ?? '')),
                'slug' => trim((string)($postData['slug'] ?? '')),
                'sort_order' => (int)($postData['sort_order'] ?? 0),
                'active' => isset($postData['active'])
            ];

            if ($valueId > 0) {
                $this->valueService->updateValue($valueId, $data);
                return _("Value updated successfully");
            } else {
                $this->valueService->createValue($data);
                return _("Value saved successfully");
            }
        } catch (\Exception $e) {
            display_error("Error saving value: " . $e->getMessage());
            throw $e; // Re-throw so ActionHandler catches it
        }
    }
}