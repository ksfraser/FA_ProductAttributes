<?php

namespace Ksfraser\FA_ProductAttributes\Test\Performance;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * NFR3 — Performance: verify the patterns used by the DAO keep query counts
 * bounded and that the database schema defines appropriate indexes.
 *
 * We cannot measure wall-clock time reliably in unit tests, so the strategy is:
 *  1. Count DB calls — any operation that would be O(N) DB round-trips for N
 *     products/categories is flagged.
 *  2. Verify the schema SQL declares indexes on every foreign-key / lookup column.
 */
class PerformanceTest extends TestCase
{
    // ------------------------------------------------------------------
    // 1. Query-count assertions
    // ------------------------------------------------------------------

    /**
     * listAssignments() must make exactly ONE DB call regardless of result size.
     */
    public function testListAssignmentsIsSingleQuery(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');

        $db->expects($this->exactly(1))
            ->method('query')
            ->willReturn([
                ['id' => 1, 'stock_id' => 'P1', 'category_id' => 1, 'value_id' => 10, 'sort_order' => 0,
                 'category_code' => 'COLOR', 'category_label' => 'Color', 'value_label' => 'Red', 'slug' => 'red'],
                ['id' => 2, 'stock_id' => 'P1', 'category_id' => 2, 'value_id' => 20, 'sort_order' => 1,
                 'category_code' => 'SIZE', 'category_label' => 'Size', 'value_label' => 'Large', 'slug' => 'large'],
            ]);

        $coreDao = $this->createMock(\Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao::class);
        $dao     = new VariationsDao($db, $coreDao);

        $result = $dao->listAssignments('P1');

        $this->assertCount(2, $result);
    }

    /**
     * listCategories() must make exactly ONE DB call.
     */
    public function testListCategoriesIsSingleQuery(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(1))
            ->method('query')
            ->willReturn([
                ['id' => 1, 'code' => 'COLOR', 'label' => 'Color', 'description' => '', 'sort_order' => 1, 'active' => 1],
            ]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao     = new VariationsDao($db, $coreDao);

        $dao->listCategories();
    }

    /**
     * addAssignment() must make exactly ONE DB call (single INSERT).
     */
    public function testAddAssignmentIsSingleExecute(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(1))->method('execute');

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao     = new VariationsDao($db, $coreDao);

        $dao->addAssignment('P1', 1, 10);
    }

    /**
     * getProductVariations() must make exactly ONE DB call.
     */
    public function testGetProductVariationsIsSingleQuery(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->expects($this->exactly(1))
            ->method('query')
            ->willReturn([
                ['stock_id' => 'P1-RED'],
                ['stock_id' => 'P1-BLUE'],
            ]);

        $coreDao = $this->createMock(ProductAttributesDao::class);
        $dao     = new VariationsDao($db, $coreDao);

        $result = $dao->getProductVariations('P1');
        $this->assertCount(2, $result);
    }

    // ------------------------------------------------------------------
    // 2. Schema index assertions (FR-level, not wall-clock)
    // ------------------------------------------------------------------

    /**
     * Verify that schema.sql defines KEY indexes for every lookup column
     * that the application queries by.
     */
    public function testSchemaSqlDefinesRequiredIndexes(): void
    {
        $schemaFile = dirname(__DIR__, 2) . '/sql/schema.sql';

        if (!is_file($schemaFile)) {
            $this->markTestSkipped('sql/schema.sql not present in this environment');
        }

        $sql = file_get_contents($schemaFile);

        $requiredIndexes = [
            // product_attribute_assignments — most queried
            'idx_stock',
            'idx_category',
            'idx_value',
            'idx_parent',
            // product_attribute_values
            'idx_category',
            // product_hierarchy
            'idx_parent',
        ];

        foreach (array_unique($requiredIndexes) as $index) {
            $this->assertStringContainsString(
                $index,
                $sql,
                "schema.sql must define index '{$index}' for query performance (NFR3)"
            );
        }
    }

    /**
     * Verify that unique constraints exist to prevent duplicate data
     * (also a correctness concern but prevents full-table scans on de-dup logic).
     */
    public function testSchemaSqlDefinesUniqueConstraints(): void
    {
        $schemaFile = dirname(__DIR__, 2) . '/sql/schema.sql';

        if (!is_file($schemaFile)) {
            $this->markTestSkipped('sql/schema.sql not present in this environment');
        }

        $sql = file_get_contents($schemaFile);

        $this->assertStringContainsString('uq_stock_category_value', $sql, 'Unique constraint on assignments prevents duplicate entries');
        $this->assertStringContainsString('uq_stock_category', $sql, 'Unique constraint on category assignments');
        $this->assertStringContainsString('uq_child', $sql, 'Unique constraint on hierarchy child_stock_id');
    }
}
