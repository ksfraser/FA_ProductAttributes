<?php

namespace Ksfraser\FA_ProductAttributes\Test\Dao;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class ProductAttributesDaoTest extends TestCase
{
    public function testListCategories(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT * FROM fa_product_attribute_categories ORDER BY sort_order, code')
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
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT id FROM fa_product_attribute_categories WHERE code = :code', ['code' => 'color'])
            ->willReturn([]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO fa_product_attribute_categories (code, label, description, sort_order, active)\nVALUES (:code, :label, :description, :sort_order, :active)",
                [
                    'code' => 'color',
                    'label' => 'Color',
                    'description' => '',
                    'sort_order' => 1,
                    'active' => 1,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->upsertCategory('color', 'Color', '', 1, true);
    }

    public function testUpsertCategoryUpdate(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT id FROM fa_product_attribute_categories WHERE code = :code', ['code' => 'color'])
            ->willReturn([['id' => 1]]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "UPDATE fa_product_attribute_categories\nSET label = :label, description = :description, sort_order = :sort_order, active = :active\nWHERE code = :code",
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

    public function testEnsureSchema(): void
    {
        $schema = $this->createMock(\Ksfraser\FA_ProductAttributes\Schema\SchemaManager::class);
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
            ->method('selectAll')
            ->with('SELECT * FROM fa_product_attribute_values WHERE category_id = :category_id ORDER BY sort_order, slug', ['category_id' => 1])
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
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT id FROM fa_product_attribute_values WHERE category_id = :category_id AND slug = :slug', ['category_id' => 1, 'slug' => 'red'])
            ->willReturn([]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "INSERT INTO fa_product_attribute_values (category_id, value, slug, sort_order, active)\nVALUES (:category_id, :value, :slug, :sort_order, :active)",
                [
                    'category_id' => 1,
                    'value' => 'Red',
                    'slug' => 'red',
                    'sort_order' => 1,
                    'active' => 1,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->upsertValue(1, 'Red', 'red', 1, true);
    }

    public function testUpsertValueUpdate(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('selectAll')
            ->with('SELECT id FROM fa_product_attribute_values WHERE category_id = :category_id AND slug = :slug', ['category_id' => 1, 'slug' => 'red'])
            ->willReturn([['id' => 1]]);
        $db->expects($this->once())
            ->method('execute')
            ->with(
                "UPDATE fa_product_attribute_values\nSET value = :value, sort_order = :sort_order, active = :active\nWHERE category_id = :category_id AND slug = :slug",
                [
                    'category_id' => 1,
                    'slug' => 'red',
                    'value' => 'Red',
                    'sort_order' => 1,
                    'active' => 1,
                ]
            );

        $dao = new ProductAttributesDao($db);
        $dao->upsertValue(1, 'Red', 'red', 1, true);
    }

    public function testListAssignments(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('selectAll')
            ->with(
                "SELECT a.*, c.code AS category_code, c.label AS category_label, c.sort_order AS category_sort_order, v.value AS value_label, v.slug AS value_slug\nFROM fa_product_attribute_assignments a\nJOIN fa_product_attribute_categories c ON c.id = a.category_id\nJOIN fa_product_attribute_values v ON v.id = a.value_id\nWHERE a.stock_id = :stock_id\nORDER BY a.sort_order, c.sort_order, c.code, v.sort_order, v.slug",
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
                "INSERT INTO fa_product_attribute_assignments (stock_id, category_id, value_id, sort_order)\nVALUES (:stock_id, :category_id, :value_id, :sort_order)",
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
}