<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Single Responsibility: Proxy/stub that delegates variation generation to
 * the fa_product_attributes_variations plugin action class.
 *
 * If the variations plugin is not loaded, returns an informative error.
 */
class GenerateVariationsAction
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $dbAdapter;

    /** @var ShippingAttributesDao|null */
    private $shippingDao;

    public function __construct(
        ProductAttributesDao $dao,
        DbAdapterInterface $dbAdapter,
        ShippingAttributesDao $shippingDao = null
    ) {
        $this->dao        = $dao;
        $this->dbAdapter  = $dbAdapter;
        $this->shippingDao = $shippingDao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        if (class_exists('Ksfraser\FA_ProductAttributes\Variations\Actions\GenerateVariationsAction')) {
            /** @var object $delegate */
            $delegate = new \Ksfraser\FA_ProductAttributes\Variations\Actions\GenerateVariationsAction(
                $this->dao,
                $this->dbAdapter,
                $this->shippingDao
            );
            return $delegate->handle($postData);
        }

        return _("Variations plugin is not loaded");
    }
}
