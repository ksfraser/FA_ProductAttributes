<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Proxy/stub that delegates product-type updates to
 * the fa_product_attributes_variations plugin action class.
 */
class UpdateProductTypesAction
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $dbAdapter)
    {
        $this->dao       = $dao;
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        if (class_exists('Ksfraser\FA_ProductAttributes_Variations\Actions\UpdateProductTypesAction')) {
            /** @var object $delegate */
            $delegate = new \Ksfraser\FA_ProductAttributes_Variations\Actions\UpdateProductTypesAction(
                $this->dao,
                $this->dbAdapter
            );
            return $delegate->handle($postData);
        }

        return _("Variations plugin is not loaded");
    }
}
