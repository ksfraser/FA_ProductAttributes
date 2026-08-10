<?php

namespace Ksfraser\FA_ProductAttributes\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductCustomAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductModifiersDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductFulfillmentDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductCategoryHierarchyDao;
use Ksfraser\FA_ProductAttributes\Dao\ShippingAttributesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductShippingClassesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductCartRulesDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductRelatedProductsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductMeasurementUnitsDao;

/**
 * Single Responsibility: Aggregate every product-attribute domain into one
 * normalized bag that external modules (Square / WooCommerce connectors) can
 * consume for API pushes.
 *
 * Consumers should call exportBag() for per-item attributes and
 * getCategoryHierarchy() for the FA stock-category tree.
 *
 * @since 1.1.0
 */
class ProductAttributeExportReader
{
    /** @var ProductAttributesDao */
    private $attributesDao;

    /** @var ProductCustomAttributesDao */
    private $customAttributesDao;

    /** @var ProductModifiersDao */
    private $modifiersDao;

    /** @var ProductFulfillmentDao */
    private $fulfillmentDao;

    /** @var ProductCategoryHierarchyDao */
    private $categoryHierarchyDao;

    /** @var ShippingAttributesDao */
    private $shippingDao;

    /** @var ProductShippingClassesDao */
    private $shippingClassesDao;

    /** @var ProductCartRulesDao */
    private $cartRulesDao;

    /** @var ProductRelatedProductsDao */
    private $relatedProductsDao;

    /** @var ProductMeasurementUnitsDao */
    private $measurementUnitsDao;

    public function __construct(
        ProductAttributesDao $attributesDao,
        ProductCustomAttributesDao $customAttributesDao,
        ProductModifiersDao $modifiersDao,
        ProductFulfillmentDao $fulfillmentDao,
        ProductCategoryHierarchyDao $categoryHierarchyDao,
        ShippingAttributesDao $shippingDao,
        ProductShippingClassesDao $shippingClassesDao,
        ProductCartRulesDao $cartRulesDao,
        ProductRelatedProductsDao $relatedProductsDao,
        ProductMeasurementUnitsDao $measurementUnitsDao
    ) {
        $this->attributesDao        = $attributesDao;
        $this->customAttributesDao  = $customAttributesDao;
        $this->modifiersDao         = $modifiersDao;
        $this->fulfillmentDao       = $fulfillmentDao;
        $this->categoryHierarchyDao = $categoryHierarchyDao;
        $this->shippingDao          = $shippingDao;
        $this->shippingClassesDao   = $shippingClassesDao;
        $this->cartRulesDao         = $cartRulesDao;
        $this->relatedProductsDao   = $relatedProductsDao;
        $this->measurementUnitsDao  = $measurementUnitsDao;
    }

    /**
     * All per-item attributes relevant to external API pushes.
     *
     * @return array<string, mixed>
     */
    public function exportBag(string $stockId): array
    {
        $shipping = $this->shippingDao->get($stockId);

        $modifierLists = $this->modifiersDao->getListsForStock($stockId);
        foreach ($modifierLists as &$list) {
            $list['modifiers'] = $this->modifiersDao->listModifiers((int)$list['id']);
        }
        unset($list);

        $shippingClass = null;
        if ($shipping !== null && !empty($shipping['shipping_class_id'])) {
            $shippingClass = $this->shippingClassesDao->get((int)$shipping['shipping_class_id']);
        }

        return [
            'attributes'        => $this->attributesDao->listAssignments($stockId),
            'custom_attributes' => $this->customAttributesDao->get($stockId),
            'modifier_lists'    => $modifierLists,
            'fulfillment'       => $this->fulfillmentDao->get($stockId),
            'shipping'          => $shipping,
            'shipping_class'    => $shippingClass,
            'cart_rules'        => $this->cartRulesDao->get($stockId),
            'related_products'  => $this->relatedProductsDao->getAll($stockId),
            'measurement_unit'  => $this->measurementUnitsDao->get($stockId),
        ];
    }

    /**
     * Parent mapping for an FA stock category (null parent = top-level).
     *
     * @return array{category_id: int, parent_category_id: int|null}
     */
    public function getCategoryHierarchy(int $categoryId): array
    {
        return [
            'category_id'        => $categoryId,
            'parent_category_id' => $this->categoryHierarchyDao->getParent($categoryId),
        ];
    }
}
