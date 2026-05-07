<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductIdentifiersDao;

/**
 * Single Responsibility: Validates and persists product identifier data.
 *
 * Expected POST keys (all optional except stock_id):
 *   stock_id         string  Parent or variation stock ID
 *   brand            string
 *   manufacturer     string
 *   mpn              string  Manufacturer Part Number
 *   gtin             string  GTIN-14
 *   ean              string  EAN-13
 *   upc              string  UPC-A
 *   isbn             string
 *   asin             string
 *   internal_barcode string
 *   supplier_part_no string
 *   model_no         string
 */
class UpsertProductIdentifiersAction
{
    /** @var ProductIdentifiersDao */
    private $dao;

    /** @var string[] */
    private static $fields = [
        'brand', 'manufacturer', 'mpn', 'gtin', 'ean', 'upc',
        'isbn', 'asin', 'internal_barcode', 'supplier_part_no', 'model_no',
    ];

    public function __construct(ProductIdentifiersDao $dao)
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

        $data = [];
        foreach (self::$fields as $field) {
            $val         = trim((string)($postData[$field] ?? ''));
            $data[$field] = ($val !== '') ? $val : null;
        }

        $this->dao->upsert($stockId, $data);

        return _('Identifiers saved');
    }
}
