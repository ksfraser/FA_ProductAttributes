<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use Ksfraser\FA_ProductAttributes\Service\ProductAttributesService;
use PHPUnit\Framework\TestCase;

class ProductAttributesServiceTest extends TestCase
{
    public function testRenderProductAttributesTabWithNoAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignedCategoriesForProduct')
            ->with('TEST123')
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'No product attributes assigned') !== false);
        $this->assertTrue(strpos($result, 'Product Attributes') !== false);
    }

    public function testRenderProductAttributesTabWithAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('getAssignedCategoriesForProduct')
            ->with('TEST123')
            ->willReturn([
                ['id' => 1, 'label' => 'Color']
            ]);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                [
                    'category_id' => 1,
                    'category_label' => 'Color',
                    'value_id' => 10,
                    'value_label' => 'Red'
                ]
            ]);
        $dao->expects($this->exactly(2))
            ->method('listCategories')
            ->willReturn([
                ['id' => 1, 'code' => 'COLOR', 'label' => 'Color']
            ]);
        $dao->expects($this->once())
            ->method('getValuesForCategory')
            ->with(1)
            ->willReturn([
                ['id' => 10, 'value' => 'Red']
            ]);

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);
        $result = $service->renderProductAttributesTab('TEST123');

        $this->assertTrue(strpos($result, 'Product Attributes') !== false);
        $this->assertTrue(strpos($result, 'Color') !== false);
        $this->assertTrue(strpos($result, 'Red') !== false);
    }

    public function testSaveProductAttributes(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 100]
            ]);
        $dao->expects($this->once())
            ->method('deleteAssignment')
            ->with(100);
        $dao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['TEST123', 1, 10],
                ['TEST123', 1, 11]
            );

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [
            'attribute_values' => [
                1 => [10, 11]
            ]
        ];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testSaveProductAttributesWithEmptyData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listAssignments');
        $dao->expects($this->never())
            ->method('deleteAssignment');
        $dao->expects($this->never())
            ->method('addAssignment');

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $postData = [];

        $service->saveProductAttributes('TEST123', $postData);
    }

    public function testDeleteProductAttributes(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listAssignments')
            ->with('TEST123')
            ->willReturn([
                ['id' => 100],
                ['id' => 101]
            ]);
        $dao->expects($this->exactly(2))
            ->method('deleteAssignment')
            ->withConsecutive(
                [100],
                [101]
            );

        $db = $this->createMock(DbAdapterInterface::class);

        $service = new ProductAttributesService($dao, $db);

        $service->deleteProductAttributes('TEST123');
    }
}