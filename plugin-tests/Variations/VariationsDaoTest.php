<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

class VariationsDaoTest extends TestCase
{
    public function testEnsureVariationsSchema(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');

        // Mock the execute calls for adding column and index
        $db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ["\n                ALTER TABLE `fa_product_attribute_assignments`\n                ADD COLUMN `parent_stock_id` VARCHAR(50) NULL DEFAULT NULL\n            "],
                ["\n                ALTER TABLE `fa_product_attribute_assignments`\n                ADD INDEX `idx_parent_stock_id` (`parent_stock_id`)\n            "],
            );

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $dao->ensureVariationsSchema();
    }

    public function testGetProductParent(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');

        $db->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                ['SELECT parent_stock_id FROM `fa_product_attribute_assignments`
                WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != \'\'
                LIMIT 1', ['stock_id' => 'ABC123']],
                ['SELECT stock_id, description FROM `fa_stock_master`
                          WHERE stock_id = :stock_id', ['stock_id' => 'PARENT123']]
            )
            ->willReturnOnConsecutiveCalls(
                [['parent_stock_id' => 'PARENT123']],
                [['stock_id' => 'PARENT123', 'description' => 'Parent Product']]
            );

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->getProductParent('ABC123');

        $this->assertEquals(['stock_id' => 'PARENT123', 'description' => 'Parent Product'], $result);
    }

    public function testGetProductParentNoResult(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT parent_stock_id FROM `fa_product_attribute_assignments`
                WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != \'\'
                LIMIT 1', ['stock_id' => 'ABC123'])
            ->willReturn([]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->getProductParent('ABC123');

        $this->assertNull($result);
    }

    public function testClearParentRelationship(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    'UPDATE `fa_product_attribute_assignments` SET parent_stock_id = NULL WHERE stock_id = :stock_id',
                    ['stock_id' => 'ABC123']
                ],
                [
                    'DELETE FROM `fa_product_hierarchy` WHERE child_stock_id = :child',
                    ['child' => 'ABC123']
                ]
            );

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $dao->clearParentRelationship('ABC123');
    }

    public function testSetParentRelationship(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    'UPDATE `fa_product_attribute_assignments` SET parent_stock_id = :parent_stock_id WHERE stock_id = :stock_id',
                    ['parent_stock_id' => 'PARENT123', 'stock_id' => 'CHILD123']
                ],
                [
                    'INSERT INTO `fa_product_hierarchy` (child_stock_id, parent_stock_id) VALUES (:child, :parent)'
                    . ' ON DUPLICATE KEY UPDATE parent_stock_id = :parent2',
                    ['child' => 'CHILD123', 'parent' => 'PARENT123', 'parent2' => 'PARENT123']
                ]
            );

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $dao->setParentRelationship('CHILD123', 'PARENT123');
    }

    public function testGetParentProductData(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `fa_stock_master` WHERE stock_id = :stock_id', ['stock_id' => 'PARENT123'])
            ->willReturn([
                ['stock_id' => 'PARENT123', 'description' => 'Parent Product', 'mb_flag' => 'B']
            ]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->getParentProductData('PARENT123');

        $this->assertEquals(['stock_id' => 'PARENT123', 'description' => 'Parent Product', 'mb_flag' => 'B'], $result);
    }

    public function testGetParentProductDataNoResult(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `fa_stock_master` WHERE stock_id = :stock_id', ['stock_id' => 'NONEXISTENT'])
            ->willReturn([]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->getParentProductData('NONEXISTENT');

        $this->assertNull($result);
    }

    public function testCreateChildProduct(): void
    {
        $parentData = [
            'stock_id' => 'PARENT123',
            'description' => 'Parent Product',
            'long_description' => 'Long description',
            'mb_flag' => 'B',
            'inactive' => 0
        ];

        $expectedChildData = [
            'stock_id' => 'CHILD123',
            'description' => 'Parent Product (Variation)',
            'long_description' => 'Long description - Variation of PARENT123',
            'mb_flag' => 'D'
        ];

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO `fa_stock_master` (stock_id, description, long_description, mb_flag) VALUES (:stock_id, :description, :long_description, :mb_flag)',
                $expectedChildData
            );

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $dao->createChildProduct('CHILD123', $parentData);
    }

    public function testCopyParentCategoryAssignments(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('execute')
            ->with(
                'INSERT INTO `fa_product_attribute_category_assignments` (stock_id, category_id)
             SELECT :child_stock_id, category_id FROM `fa_product_attribute_category_assignments`
             WHERE stock_id = :parent_stock_id',
                ['child_stock_id' => 'CHILD123', 'parent_stock_id' => 'PARENT123']
            );

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $dao->copyParentCategoryAssignments('CHILD123', 'PARENT123');
    }

    public function testGetProductVariations(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT stock_id, description FROM `fa_stock_master`
                WHERE stock_id IN (
                    SELECT stock_id FROM `fa_product_attribute_assignments`
                    WHERE parent_stock_id = :parent_stock_id
                )', ['parent_stock_id' => 'PARENT123'])
            ->willReturn([
                ['stock_id' => 'CHILD1', 'description' => 'Child 1'],
                ['stock_id' => 'CHILD2', 'description' => 'Child 2']
            ]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->getProductVariations('PARENT123');

        $expected = [
            ['stock_id' => 'CHILD1', 'description' => 'Child 1'],
            ['stock_id' => 'CHILD2', 'description' => 'Child 2']
        ];
        $this->assertEquals($expected, $result);
    }

    public function testIsVariation(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments`
                WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != \'\'', ['stock_id' => 'CHILD123'])
            ->willReturn([['count' => 1]]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->isVariation('CHILD123');

        $this->assertTrue($result);
    }

    public function testIsVariationFalse(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments`
                WHERE stock_id = :stock_id AND parent_stock_id IS NOT NULL AND parent_stock_id != \'\'', ['stock_id' => 'PARENT123'])
            ->willReturn([['count' => 0]]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao = new VariationsDao($db, $coreDao);
        $result = $dao->isVariation('PARENT123');

        $this->assertFalse($result);
    }
}