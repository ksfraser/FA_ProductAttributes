<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;

/**
 * Single Responsibility: Validates and persists shipping/logistics attributes for a product.
 *
 * Accepted POST keys: stock_id, length, width, height, dim_unit, weight, weight_unit,
 * is_hazardous, hazmat_class, un_number, proper_shipping_name, packing_group,
 * is_fragile, is_stackable, is_oversize, is_perishable,
 * temperature_sensitive, temp_min, temp_max, temp_unit,
 * hs_code, country_of_origin, customs_description, declared_value.
 */
class UpsertShippingAttributesAction
{
    /** @var ShippingAttributesDao */
    private $dao;

    public function __construct(ShippingAttributesDao $dao)
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

        $rawDimUnit  = (string)($postData['dim_unit'] ?? 'cm');
        $dimUnit     = in_array($rawDimUnit, ['cm', 'in'], true) ? $rawDimUnit : 'cm';

        $rawWeightUnit = (string)($postData['weight_unit'] ?? 'kg');
        $weightUnit    = in_array($rawWeightUnit, ['kg', 'lb', 'g', 'oz'], true) ? $rawWeightUnit : 'kg';

        $rawTempUnit = (string)($postData['temp_unit'] ?? 'C');
        $tempUnit    = in_array($rawTempUnit, ['C', 'F'], true) ? $rawTempUnit : 'C';

        $rawPackGrp = (string)($postData['packing_group'] ?? '');
        $packingGrp = in_array($rawPackGrp, ['I', 'II', 'III', ''], true)
                        ? ($rawPackGrp !== '' ? $rawPackGrp : null)
                        : null;

        $data = [
            'length'                => $this->nullableFloat($postData['length'] ?? null),
            'width'                 => $this->nullableFloat($postData['width'] ?? null),
            'height'                => $this->nullableFloat($postData['height'] ?? null),
            'dim_unit'              => $dimUnit,
            'weight'                => $this->nullableFloat($postData['weight'] ?? null),
            'weight_unit'           => $weightUnit,
            'is_hazardous'          => (int)(bool)($postData['is_hazardous'] ?? 0),
            'hazmat_class'          => $this->nullableString($postData['hazmat_class'] ?? null),
            'un_number'             => $this->nullableString($postData['un_number'] ?? null),
            'proper_shipping_name'  => $this->nullableString($postData['proper_shipping_name'] ?? null),
            'packing_group'         => $packingGrp,
            'is_fragile'            => (int)(bool)($postData['is_fragile'] ?? 0),
            'is_stackable'          => (int)(bool)($postData['is_stackable'] ?? 1),
            'is_oversize'           => (int)(bool)($postData['is_oversize'] ?? 0),
            'is_perishable'         => (int)(bool)($postData['is_perishable'] ?? 0),
            'temperature_sensitive' => (int)(bool)($postData['temperature_sensitive'] ?? 0),
            'temp_min'              => $this->nullableFloat($postData['temp_min'] ?? null),
            'temp_max'              => $this->nullableFloat($postData['temp_max'] ?? null),
            'temp_unit'             => $tempUnit,
            'hs_code'               => $this->nullableString($postData['hs_code'] ?? null),
            'country_of_origin'     => $this->nullableString($postData['country_of_origin'] ?? null),
            'customs_description'   => $this->nullableString($postData['customs_description'] ?? null),
            'declared_value'        => $this->nullableFloat($postData['declared_value'] ?? null),
        ];

        $this->dao->upsert($stockId, $data);

        return 'Shipping attributes saved';
    }

    /** @param mixed $val */
    private function nullableFloat($val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        return (float)$val;
    }

    /** @param mixed $val */
    private function nullableString($val): ?string
    {
        $s = trim((string)($val ?? ''));
        return $s !== '' ? $s : null;
    }
}
