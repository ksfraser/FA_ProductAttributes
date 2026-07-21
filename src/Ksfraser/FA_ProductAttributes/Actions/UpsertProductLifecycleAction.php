<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductLifecycleDao;

/**
 * Single Responsibility: Validates and persists product lifecycle / status flags.
 *
 * Expected POST keys:
 *   stock_id                string   Required
 *   status                  string   active|draft|discontinued|archived
 *   is_special_order        1|0
 *   is_clearance            1|0
 *   is_out_of_stock_notice  1|0
 *   is_new_arrival          1|0
 *   is_bestseller           1|0
 *   is_featured             1|0
 *   is_seasonal             1|0
 *   available_from          string   YYYY-MM-DD or empty
 *   discontinue_on          string   YYYY-MM-DD or empty
 *   clearance_note          string
 */
class UpsertProductLifecycleAction
{
    /** @var ProductLifecycleDao */
    private $dao;

    /** @var string[] */
    private static $validStatuses = ['active', 'draft', 'discontinued', 'archived'];

    /** @var string[] */
    private static $boolFlags = [
        'is_special_order', 'is_clearance', 'is_out_of_stock_notice',
        'is_new_arrival', 'is_bestseller', 'is_featured', 'is_seasonal',
    ];

    public function __construct(ProductLifecycleDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param string $stockId
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(string $stockId, array $postData): string
    {
        if (trim($stockId) === '') {
            return 'Invalid stock ID';
        }

        $rawStatus = (string)($postData['status'] ?? 'active');
        $status    = in_array($rawStatus, self::$validStatuses, true) ? $rawStatus : 'active';

        $data = ['status' => $status];

        foreach (self::$boolFlags as $flag) {
            $data[$flag] = (int)(bool)($postData[$flag] ?? 0);
        }

        $data['available_from']  = $this->nullableDate($postData['available_from'] ?? null);
        $data['discontinue_on']  = $this->nullableDate($postData['discontinue_on'] ?? null);
        $data['clearance_note']  = $this->nullableString($postData['clearance_note'] ?? null);

        $this->dao->upsert($stockId, $data);

        return _('Lifecycle saved');
    }

    /**
     * @param mixed $val
     */
    private function nullableDate($val): ?string
    {
        $str = trim((string)($val ?? ''));
        if ($str === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return null;
        }
        return $str;
    }

    /**
     * @param mixed $val
     */
    private function nullableString($val): ?string
    {
        $str = trim((string)($val ?? ''));
        return ($str !== '') ? $str : null;
    }
}
