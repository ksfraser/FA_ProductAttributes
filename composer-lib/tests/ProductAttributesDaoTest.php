<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductAttributesDaoTest extends TestCase
{
    public function testEnsureSchema(): void
    {
        $schema = $this->createMock(\Ksfraser\SchemaManager\SchemaManager::class);
        $db = $this->createMock(DbAdapterInterface::class);
        
        $schema->expects($this->once())
            ->method('ensureSchema')
            ->with($db);

        $dao = new ProductAttributesDao($db, $schema);
        $dao->ensureSchema();
    }

    public function testListAssignments(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT a.*, c.code AS category_code, c.label AS category_label, c.sort_order AS category_sort_order, v.value AS value_label, v.slug AS value_slug\nFROM `fa_product_attribute_assignments` a\nJOIN `fa_product_attribute_categories` c ON c.id = a.category_id\nJOIN `fa_product_attribute_values` v ON v.id = a.value_id\nWHERE a.stock_id = :stock_id\nORDER BY a.sort_order, c.sort_order, c.code, v.sort_order, v.slug",
                ['stock_id' => 'ABC123']
            )
            ->willReturn([
                ['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'category_code' => 'color', 'category_label' => 'Color', 'value_label' => 'Red', 'value_slug' => 'red'],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->listAssignments('ABC123');

        $this->assertCount(1, $result);
        $this->assertEquals('color', $result[0]['category_code']);
    }

    public function testAddAssignment(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO `fa_product_attribute_assignments` (stock_id, category_id, value_id, sort_order)\nVALUES (:stock_id, :category_id, :value_id, :sort_order)",
                [
                    'stock_id' => 'ABC123',
                    'category_id' => 1,
                    'value_id' => 1,
                    'sort_order' => 0,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->addAssignment('ABC123', 1, 1, 0);
    }

    public function testGetAssignedCategoriesForProduct(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT c.* FROM `fa_product_attribute_categories` c\r\n             INNER JOIN `fa_product_attribute_category_assignments` pca ON c.id = pca.category_id\r\n             WHERE pca.stock_id = :stock_id\r\n             ORDER BY c.sort_order, c.code",
                ['stock_id' => 'ABC123']
            )
            ->willReturn([
                ['id' => 1, 'code' => 'color', 'label' => 'Color', 'sort_order' => 1],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getAssignedCategoriesForProduct('ABC123');

        $this->assertCount(1, $result);
        $this->assertEquals('color', $result[0]['code']);
    }

    public function testGetVariationCountForProductCategory(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        // Method is deprecated and returns 0 for backward compatibility

        $dao = new ProductAttributesDao($db);
        $result = $dao->getVariationCountForProductCategory('ABC123', 1);

        $this->assertEquals(0, $result);
    }

    public function testDeleteAssignment(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with('DELETE FROM `fa_product_attribute_assignments` WHERE id = :id', ['id' => 123]);

        $dao = new ProductAttributesDao($db);
        $dao->deleteAssignment(123);
    }

    public function testListCategoryAssignments(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with("SELECT c.* FROM `fa_product_attribute_categories` c
             INNER JOIN `fa_product_attribute_category_assignments` pca ON c.id = pca.category_id
             WHERE pca.stock_id = :stock_id
             ORDER BY c.sort_order, c.code", ['stock_id' => 'ABC123'])
            ->willReturn([
                ['id' => 1, 'code' => 'COLOR', 'label' => 'Color'],
                ['id' => 2, 'code' => 'SIZE', 'label' => 'Size'],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->listCategoryAssignments('ABC123');

        $this->assertEquals([
            ['id' => 1, 'code' => 'COLOR', 'label' => 'Color'],
            ['id' => 2, 'code' => 'SIZE', 'label' => 'Size'],
        ], $result);
    }

    public function testAddCategoryAssignment(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with("INSERT INTO `fa_product_attribute_category_assignments` (stock_id, category_id)
             VALUES (:stock_id, :category_id)", [
                'stock_id' => 'ABC123',
                'category_id' => 1
            ]);

        $dao = new ProductAttributesDao($db);
        $dao->addCategoryAssignment('ABC123', 1);
    }

    public function testGetProductsByType(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT stock_id, description FROM `fa_stock_master`
                WHERE mb_flag IN (?,?)', ['B', 'M'])
            ->willReturn([
                ['stock_id' => 'ABC123', 'description' => 'Test Product'],
                ['stock_id' => 'DEF456', 'description' => 'Another Product'],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getProductsByType(['B', 'M']);

        $expected = [
            ['stock_id' => 'ABC123', 'description' => 'Test Product'],
            ['stock_id' => 'DEF456', 'description' => 'Another Product'],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetAllProducts(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT stock_id, description FROM `fa_stock_master`
                ORDER BY stock_id')
            ->willReturn([
                ['stock_id' => 'ABC123', 'description' => 'Test Product'],
                ['stock_id' => 'DEF456', 'description' => 'Another Product'],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getAllProducts();

        $expected = [
            ['stock_id' => 'ABC123', 'description' => 'Test Product'],
            ['stock_id' => 'DEF456', 'description' => 'Another Product'],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testRemoveCategoryAssignment(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'DELETE FROM `fa_product_attribute_category_assignments`
             WHERE stock_id = :stock_id AND category_id = :category_id',
                ['stock_id' => 'ABC123', 'category_id' => 5]
            );

        $dao = new ProductAttributesDao($db);
        $dao->removeCategoryAssignment('ABC123', 5);
    }

    public function testGetDbAdapter(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $dao = new ProductAttributesDao($db);

        $result = $dao->getDbAdapter();
        $this->assertSame($db, $result);
    }
}