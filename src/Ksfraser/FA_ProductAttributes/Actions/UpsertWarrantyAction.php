<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductWarrantyDao;

/**
 * Single Responsibility: Validates and persists product warranty data.
 *
 * Expected POST keys (all optional except stock_id):
 *   stock_id                      string
 *   warranty_type                 string  none|manufacturer|extended|third_party|lifetime
 *   manufacturer_duration         int
 *   manufacturer_duration_unit    string  days|months|years
 *   extended_duration             int
 *   extended_duration_unit        string  days|months|years
 *   third_party_duration          int
 *   third_party_duration_unit     string  days|months|years
 *   lifetime_notes                string
 *   warranty_notes                string
 */
class UpsertWarrantyAction
{
    /** @var ProductWarrantyDao */
    private $dao;

    public function __construct(ProductWarrantyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $stockId = trim((string)($postData['stock_id'] ?? ''));
        if ($stockId === '') {
            return 'Invalid stock ID';
        }

        $rawType = (string)($postData['warranty_type'] ?? 'none');
        $validTypes = ['none', 'manufacturer', 'extended', 'third_party', 'lifetime'];
        $type = in_array($rawType, $validTypes, true) ? $rawType : 'none';

        $validUnits = ['days', 'months', 'years'];

        $rawMfgUnit = (string)($postData['manufacturer_duration_unit'] ?? 'months');
        $mfgUnit = in_array($rawMfgUnit, $validUnits, true) ? $rawMfgUnit : 'months';

        $rawExtUnit = (string)($postData['extended_duration_unit'] ?? 'months');
        $extUnit = in_array($rawExtUnit, $validUnits, true) ? $rawExtUnit : 'months';

        $rawTpUnit = (string)($postData['third_party_duration_unit'] ?? 'months');
        $tpUnit = in_array($rawTpUnit, $validUnits, true) ? $rawTpUnit : 'months';

        $data = [
            'warranty_type'                => $type,
            'manufacturer_duration'        => $this->nullableInt($postData['manufacturer_duration'] ?? null),
            'manufacturer_duration_unit'   => $mfgUnit,
            'extended_duration'            => $this->nullableInt($postData['extended_duration'] ?? null),
            'extended_duration_unit'       => $extUnit,
            'third_party_duration'         => $this->nullableInt($postData['third_party_duration'] ?? null),
            'third_party_duration_unit'    => $tpUnit,
            'lifetime_notes'               => $this->nullableString($postData['lifetime_notes'] ?? null),
            'warranty_notes'               => $this->nullableString($postData['warranty_notes'] ?? null),
        ];

        $this->dao->upsert($stockId, $data);

        return _('Warranty saved');
    }

    /** @param mixed $val */
    private function nullableInt($val): ?int
    {
        if ($val === null || $val === '') {
            return null;
        }
        return (int)$val;
    }

    /** @param mixed $val */
    private function nullableString($val): ?string
    {
        $s = trim((string)($val ?? ''));
        return $s !== '' ? $s : null;
    }
}
