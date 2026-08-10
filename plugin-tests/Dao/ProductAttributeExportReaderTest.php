<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

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
use Ksfraser\FA_ProductAttributes\Service\ProductAttributeExportReader;
use PHPUnit\Framework\TestCase;

class ProductAttributeExportReaderTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $attributesDao;

    /** @var ProductCustomAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $customAttributesDao;

    /** @var ProductModifiersDao|\PHPUnit\Framework\MockObject\MockObject */
    private $modifiersDao;

    /** @var ProductFulfillmentDao|\PHPUnit\Framework\MockObject\MockObject */
    private $fulfillmentDao;

    /** @var ProductCategoryHierarchyDao|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryHierarchyDao;

    /** @var ShippingAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingDao;

    /** @var ProductShippingClassesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingClassesDao;

    /** @var ProductCartRulesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $cartRulesDao;

    /** @var ProductRelatedProductsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedProductsDao;

    /** @var ProductMeasurementUnitsDao|\PHPUnit\Framework\MockObject\MockObject */
    private $measurementUnitsDao;

    /** @var ProductAttributeExportReader */
    private $reader;

    protected function setUp(): void
    {
        $this->attributesDao       = $this->createMock(ProductAttributesDao::class);
        $this->customAttributesDao = $this->createMock(ProductCustomAttributesDao::class);
        $this->modifiersDao        = $this->createMock(ProductModifiersDao::class);
        $this->fulfillmentDao      = $this->createMock(ProductFulfillmentDao::class);
        $this->categoryHierarchyDao = $this->createMock(ProductCategoryHierarchyDao::class);
        $this->shippingDao         = $this->createMock(ShippingAttributesDao::class);
        $this->shippingClassesDao  = $this->createMock(ProductShippingClassesDao::class);
        $this->cartRulesDao        = $this->createMock(ProductCartRulesDao::class);
        $this->relatedProductsDao  = $this->createMock(ProductRelatedProductsDao::class);
        $this->measurementUnitsDao = $this->createMock(ProductMeasurementUnitsDao::class);

        $this->reader = new ProductAttributeExportReader(
            $this->attributesDao,
            $this->customAttributesDao,
            $this->modifiersDao,
            $this->fulfillmentDao,
            $this->categoryHierarchyDao,
            $this->shippingDao,
            $this->shippingClassesDao,
            $this->cartRulesDao,
            $this->relatedProductsDao,
            $this->measurementUnitsDao
        );
    }

    public function testExportBagContainsAllAttributeDomains(): void
    {
        $this->attributesDao->method('listAssignments')->willReturn([
            ['id' => 1, 'category_code' => 'color', 'value_label' => 'Red', 'is_default' => 1],
        ]);
        $this->customAttributesDao->method('get')->willReturn([
            ['attr_key' => 'origin', 'attr_value' => 'CA'],
        ]);
        $this->modifiersDao->method('getListsForStock')->willReturn([
            ['id' => 2, 'name' => 'Toppings', 'sort_order' => 1],
        ]);
        $this->modifiersDao->method('listModifiers')->willReturn([
            ['id' => 3, 'name' => 'Extra cheese', 'price' => '1.50'],
        ]);
        $this->fulfillmentDao->method('get')->willReturn([
            'stock_id' => 'SKU001', 'product_type' => 'SERVICE', 'service_duration_minutes' => '60',
        ]);
        $this->shippingDao->method('get')->willReturn([
            'stock_id' => 'SKU001', 'shipping_class_id' => 4, 'weight' => '1.500',
        ]);
        $this->shippingClassesDao->method('get')->willReturn([
            'id' => 4, 'name' => 'Hazardous', 'slug' => 'hazardous',
        ]);
        $this->cartRulesDao->method('get')->willReturn([
            'stock_id' => 'SKU001', 'sold_individually' => 1,
        ]);
        $this->relatedProductsDao->method('getAll')->willReturn([
            ['related_stock_id' => 'SKU002', 'relation_type' => 'upsell', 'sort_order' => 1],
        ]);
        $this->measurementUnitsDao->method('get')->willReturn([
            'stock_id' => 'SKU001', 'measurement_unit_id' => 'measurement_unit:1',
        ]);

        $bag = $this->reader->exportBag('SKU001');

        $this->assertCount(1, $bag['attributes']);
        $this->assertSame('CA', $bag['custom_attributes'][0]['attr_value']);
        $this->assertSame('Toppings', $bag['modifier_lists'][0]['name']);
        $this->assertSame('Extra cheese', $bag['modifier_lists'][0]['modifiers'][0]['name']);
        $this->assertSame('SERVICE', $bag['fulfillment']['product_type']);
        $this->assertSame('hazardous', $bag['shipping_class']['slug']);
        $this->assertSame(1, $bag['cart_rules']['sold_individually']);
        $this->assertSame('SKU002', $bag['related_products'][0]['related_stock_id']);
        $this->assertSame('measurement_unit:1', $bag['measurement_unit']['measurement_unit_id']);
    }

    public function testExportBagLeavesShippingClassNullWithoutReference(): void
    {
        $this->attributesDao->method('listAssignments')->willReturn([]);
        $this->customAttributesDao->method('get')->willReturn([]);
        $this->modifiersDao->method('getListsForStock')->willReturn([]);
        $this->fulfillmentDao->method('get')->willReturn(null);
        $this->shippingDao->method('get')->willReturn(['stock_id' => 'SKU001', 'shipping_class_id' => null]);
        $this->cartRulesDao->method('get')->willReturn(null);
        $this->relatedProductsDao->method('getAll')->willReturn([]);
        $this->measurementUnitsDao->method('get')->willReturn(null);

        $bag = $this->reader->exportBag('SKU001');

        $this->assertNull($bag['shipping_class']);
        $this->assertNull($bag['fulfillment']);
        $this->assertNull($bag['cart_rules']);
        $this->assertNull($bag['measurement_unit']);
    }

    public function testGetCategoryHierarchyResolvesParent(): void
    {
        $this->categoryHierarchyDao->method('getParent')->willReturn(3);

        $result = $this->reader->getCategoryHierarchy(10);

        $this->assertSame(['category_id' => 10, 'parent_category_id' => 3], $result);
    }

    public function testGetCategoryHierarchyReturnsNullParentWhenNone(): void
    {
        $this->categoryHierarchyDao->method('getParent')->willReturn(null);

        $result = $this->reader->getCategoryHierarchy(10);

        $this->assertSame(['category_id' => 10, 'parent_category_id' => null], $result);
    }
}
