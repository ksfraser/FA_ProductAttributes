<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductAttributesDaoTest extends TestCase
{
    public function testListCategories(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `fa_product_attribute_categories` ORDER BY sort_order, code')
            ->willReturn([
                ['id' => 1, 'code' => 'color', 'label' => 'Color', 'sort_order' => 2],
                ['id' => 2, 'code' => 'size', 'label' => 'Size', 'sort_order' => 1],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->listCategories();

        $this->assertCount(2, $result);
        $this->assertEquals('color', $result[0]['code']);
    }

    public function testUpsertCategoryInsert(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('lastInsertId')->willReturn(1);
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT id FROM `fa_product_attribute_categories` WHERE code = :code', ['code' => 'color'])
            ->willReturn([]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO `fa_product_attribute_categories` (code, label, description, sort_order, active)\nVALUES (:code, :label, :description, :sort_order, :active)",
                [
                    'code' => 'color',
                    'label' => 'Color',
                    'description' => '',
                    'sort_order' => 1,
                    'active' => 1,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $result = $dao->upsertCategory('color', 'Color', '', 1, true);
        $this->assertEquals(1, $result);
    }

    public function testUpsertCategoryUpdate(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT id FROM `fa_product_attribute_categories` WHERE code = :code', ['code' => 'color'])
            ->willReturn([['id' => 1]]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "UPDATE `fa_product_attribute_categories`\nSET label = :label, description = :description, sort_order = :sort_order, active = :active\nWHERE code = :code",
                [
                    'label' => 'Color',
                    'description' => '',
                    'sort_order' => 1,
                    'active' => 1,
                    'code' => 'color',
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->upsertCategory('color', 'Color', '', 1, true);
    }

    public function testUpsertCategoryUpdateById(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "UPDATE `fa_product_attribute_categories`\nSET code = :code, label = :label, description = :description, sort_order = :sort_order, active = :active\nWHERE id = :id",
                [
                    'id' => 5,
                    'code' => 'color',
                    'label' => 'Color',
                    'description' => 'Color description',
                    'sort_order' => 2,
                    'active' => 0,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->upsertCategory('color', 'Color', 'Color description', 2, false, 5);
    }

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

    public function testListValues(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `fa_product_attribute_values` WHERE category_id = :category_id ORDER BY sort_order, slug', ['category_id' => 1])
            ->willReturn([
                ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 1],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->listValues(1);

        $this->assertCount(1, $result);
        $this->assertEquals('Red', $result[0]['value']);
    }

    public function testUpsertValueInsert(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('lastInsertId')->willReturn(1);
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT id FROM `fa_product_attribute_values` WHERE category_id = :category_id AND slug = :slug', ['category_id' => 1, 'slug' => 'red'])
            ->willReturn([]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO `fa_product_attribute_values` (category_id, value, slug, sort_order, active)\nVALUES (:category_id, :value, :slug, :sort_order, :active)",
                [
                    'category_id' => 1,
                    'value' => 'Red',
                    'slug' => 'red',
                    'sort_order' => 1,
                    'active' => 1,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $result = $dao->upsertValue('1', 'Red', 'red', 1, true);
        $this->assertEquals(1, $result);
    }

    public function testUpsertValueUpdate(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT id FROM `fa_product_attribute_values` WHERE category_id = :category_id AND slug = :slug', ['category_id' => 1, 'slug' => 'red'])
            ->willReturn([['id' => 1]]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "UPDATE `fa_product_attribute_values`\nSET value = :value, sort_order = :sort_order, active = :active\nWHERE category_id = :category_id AND slug = :slug",
                [
                    'category_id' => 1,
                    'slug' => 'red',
                    'value' => 'Red',
                    'sort_order' => 1,
                    'active' => 1,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->upsertValue('1', 'Red', 'red', 1, true);
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
                "INSERT INTO `fa_product_attribute_assignments` (stock_id, category_id, value_id, sort_order, parent_stock_id)\nVALUES (:stock_id, :category_id, :value_id, :sort_order, :parent_stock_id)",
                [
                    'stock_id' => 'ABC123',
                    'category_id' => 1,
                    'value_id' => 1,
                    'sort_order' => 0,
                    'parent_stock_id' => null,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->addAssignment('ABC123', 1, 1, 0);
    }

    public function testAddAssignmentWithParentStockId(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO `fa_product_attribute_assignments` (stock_id, category_id, value_id, sort_order, parent_stock_id)\nVALUES (:stock_id, :category_id, :value_id, :sort_order, :parent_stock_id)",
                [
                    'stock_id' => 'ABC123-Red',
                    'category_id' => 1,
                    'value_id' => 1,
                    'sort_order' => 1,
                    'parent_stock_id' => 'ABC123',
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->addAssignment('ABC123-Red', 1, 1, 1, 'ABC123');
    }

    public function testAssignValuesEmptyPairsReturnsEmptyWithoutQuery(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->never())->method('query');

        $dao = new ProductAttributesDao($db);
        $result = $dao->assignValues('ABC123', []);

        $this->assertSame([], $result);
    }

    public function testAssignValuesInsertsMissingPairsSkipsInvalidAndDuplicates(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT a.*, c.code AS category_code, c.label AS category_label, c.sort_order AS category_sort_order, v.value AS value_label, v.slug AS value_slug\n"
                . "FROM `fa_product_attribute_assignments` a\n"
                . "JOIN `fa_product_attribute_categories` c ON c.id = a.category_id\n"
                . "JOIN `fa_product_attribute_values` v ON v.id = a.value_id\n"
                . "WHERE a.stock_id = :stock_id\n"
                . "ORDER BY a.sort_order, c.sort_order, c.code, v.sort_order, v.slug",
                ['stock_id' => 'ABC123']
            )
            ->willReturn([]);
        $db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    "INSERT INTO `fa_product_attribute_assignments` (stock_id, category_id, value_id, sort_order, parent_stock_id)\nVALUES (:stock_id, :category_id, :value_id, :sort_order, :parent_stock_id)",
                    ['stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'sort_order' => 0, 'parent_stock_id' => null],
                ],
                [
                    "INSERT INTO `fa_product_attribute_assignments` (stock_id, category_id, value_id, sort_order, parent_stock_id)\nVALUES (:stock_id, :category_id, :value_id, :sort_order, :parent_stock_id)",
                    ['stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 2, 'sort_order' => 5, 'parent_stock_id' => null],
                ]
            );

        $dao = new ProductAttributesDao($db);
        $result = $dao->assignValues('ABC123', [
            ['category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
            ['category_id' => 1, 'value_id' => 1, 'sort_order' => 9],
            ['category_id' => 0, 'value_id' => 5],
            ['category_id' => 1, 'value_id' => 2, 'sort_order' => 5],
        ]);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['category_id']);
        $this->assertEquals(1, $result[0]['value_id']);
        $this->assertEquals(2, $result[1]['value_id']);
    }

    public function testAssignValuesSkipsExistingPairs(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT a.*, c.code AS category_code, c.label AS category_label, c.sort_order AS category_sort_order, v.value AS value_label, v.slug AS value_slug\n"
                . "FROM `fa_product_attribute_assignments` a\n"
                . "JOIN `fa_product_attribute_categories` c ON c.id = a.category_id\n"
                . "JOIN `fa_product_attribute_values` v ON v.id = a.value_id\n"
                . "WHERE a.stock_id = :stock_id\n"
                . "ORDER BY a.sort_order, c.sort_order, c.code, v.sort_order, v.slug",
                ['stock_id' => 'ABC123']
            )
            ->willReturn([
                ['id' => 1, 'stock_id' => 'ABC123', 'category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
            ]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO `fa_product_attribute_assignments` (stock_id, category_id, value_id, sort_order, parent_stock_id)\nVALUES (:stock_id, :category_id, :value_id, :sort_order, :parent_stock_id)",
                ['stock_id' => 'ABC123', 'category_id' => 2, 'value_id' => 3, 'sort_order' => 4, 'parent_stock_id' => null]
            );

        $dao = new ProductAttributesDao($db);
        $result = $dao->assignValues('ABC123', [
            ['category_id' => 1, 'value_id' => 1, 'sort_order' => 0],
            ['category_id' => 2, 'value_id' => 3, 'sort_order' => 4],
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['category_id']);
        $this->assertEquals(3, $result[0]['value_id']);
    }

    public function testGetAssignedCategoriesForProduct(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT c.* FROM `fa_product_attribute_categories` c\n             INNER JOIN `fa_product_attribute_category_assignments` pca ON c.id = pca.category_id\n             WHERE pca.stock_id = :stock_id\n             ORDER BY c.sort_order, c.code",
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

    public function testGetValuesForCategory(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `fa_product_attribute_values` WHERE category_id = :category_id ORDER BY sort_order, slug', ['category_id' => 1])
            ->willReturn([
                ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 1],
                ['id' => 2, 'category_id' => 1, 'value' => 'Blue', 'slug' => 'blue', 'sort_order' => 2],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getValuesForCategory(1);

        $this->assertCount(2, $result);
        $this->assertEquals('Red', $result[0]['value']);
        $this->assertEquals('Blue', $result[1]['value']);
    }

    public function testGetVariationCountForProductCategory(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `fa_product_attribute_values` WHERE category_id = :category_id ORDER BY sort_order, slug', ['category_id' => 1])
            ->willReturn([
                ['id' => 1, 'value' => 'Red'],
                ['id' => 2, 'value' => 'Blue'],
                ['id' => 3, 'value' => 'Green'],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getVariationCountForProductCategory('ABC123', 1);

        $this->assertEquals(3, $result);
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

    public function testDeleteCategory(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['DELETE FROM `fa_product_attribute_assignments` WHERE category_id = :category_id', ['category_id' => 1]],
                ['DELETE FROM `fa_product_attribute_values` WHERE category_id = :category_id', ['category_id' => 1]],
                ['DELETE FROM `fa_product_attribute_categories` WHERE id = :id', ['id' => 1]]
            );

        $dao = new ProductAttributesDao($db);
        $dao->deleteCategory(1);
    }

    public function testDeleteValue(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['DELETE FROM `fa_product_attribute_assignments` WHERE value_id = :value_id', ['value_id' => 1]],
                ['DELETE FROM `fa_product_attribute_values` WHERE id = :id', ['id' => 1]]
            );

        $dao = new ProductAttributesDao($db);
        $dao->deleteValue(1);
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

    public function testSetProductParentInserts(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO `fa_product_hierarchy` (child_stock_id, parent_stock_id) VALUES (:child, :parent)'
                . ' ON DUPLICATE KEY UPDATE parent_stock_id = :parent2',
                ['child' => 'CHILD001', 'parent' => 'PARENT001', 'parent2' => 'PARENT001']
            );

        $dao = new ProductAttributesDao($db);
        $dao->setProductParent('CHILD001', 'PARENT001');
    }

    public function testSetProductParentDeletesWhenNull(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'DELETE FROM `fa_product_hierarchy` WHERE child_stock_id = :child',
                ['child' => 'CHILD001']
            );

        $dao = new ProductAttributesDao($db);
        $dao->setProductParent('CHILD001', null);
    }

    public function testGetProductParentReturnsParent(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                'SELECT parent_stock_id FROM `fa_product_hierarchy` WHERE child_stock_id = :child',
                ['child' => 'CHILD001']
            )
            ->willReturn([['parent_stock_id' => 'PARENT001']]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getProductParent('CHILD001');
        $this->assertEquals('PARENT001', $result);
    }

    public function testGetProductParentReturnsNullWhenNone(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->getProductParent('CHILD001');
        $this->assertNull($result);
    }

    public function testListDefaultAssignments(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                "SELECT a.*, c.code AS category_code, c.label AS category_label, c.sort_order AS category_sort_order, v.value AS value_label, v.slug AS value_slug\n"
                . "FROM `fa_product_attribute_assignments` a\n"
                . "JOIN `fa_product_attribute_categories` c ON c.id = a.category_id\n"
                . "JOIN `fa_product_attribute_values` v ON v.id = a.value_id\n"
                . "WHERE a.stock_id = :stock_id AND a.is_default = 1\n"
                . "ORDER BY a.sort_order, c.sort_order, c.code, v.sort_order, v.slug",
                ['stock_id' => 'ABC123']
            )
            ->willReturn([
                ['id' => 1, 'category_code' => 'size', 'value_label' => 'M', 'is_default' => 1],
            ]);

        $dao = new ProductAttributesDao($db);
        $result = $dao->listDefaultAssignments('ABC123');

        $this->assertCount(1, $result);
        $this->assertSame('M', $result[0]['value_label']);
    }

    public function testSetAssignmentDefaultTrue(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE `fa_product_attribute_assignments` SET is_default = :is_default WHERE id = :id',
                ['is_default' => 1, 'id' => 7]
            );

        $dao = new ProductAttributesDao($db);
        $dao->setAssignmentDefault(7, true);
    }

    public function testSetAssignmentDefaultFalse(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE `fa_product_attribute_assignments` SET is_default = :is_default WHERE id = :id',
                ['is_default' => 0, 'id' => 7]
            );

        $dao = new ProductAttributesDao($db);
        $dao->setAssignmentDefault(7, false);
    }

    public function testUpdateValueColorSetsColor(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE `fa_product_attribute_values` SET color = :color WHERE id = :id',
                ['color' => '#FF0000', 'id' => 3]
            );

        $dao = new ProductAttributesDao($db);
        $dao->updateValueColor(3, '#FF0000');
    }

    public function testUpdateValueColorClearsColor(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'UPDATE `fa_product_attribute_values` SET color = :color WHERE id = :id',
                ['color' => null, 'id' => 3]
            );

        $dao = new ProductAttributesDao($db);
        $dao->updateValueColor(3, null);
    }
}