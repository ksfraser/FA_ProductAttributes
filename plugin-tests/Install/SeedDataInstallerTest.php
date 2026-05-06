<?php

namespace Ksfraser\FA_ProductAttributes\Test\Install;

use Ksfraser\FA_ProductAttributes\Install\SeedDataInstaller;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class SeedDataInstallerTest extends TestCase
{
    /** @var ProductAttributesDao|\PHPUnit\Framework\MockObject\MockObject */
    private $dao;

    /** @var SeedDataInstaller */
    private $installer;

    protected function setUp(): void
    {
        $this->dao       = $this->createMock(ProductAttributesDao::class);
        $this->installer = new SeedDataInstaller($this->dao);
    }

    public function testIsSeededReturnsFalseWhenNoCategoriesExist(): void
    {
        $this->dao->method('listCategories')->willReturn([]);
        $this->assertFalse($this->installer->isSeeded());
    }

    public function testIsSeededReturnsTrueWhenCategoriesExist(): void
    {
        $this->dao->method('listCategories')->willReturn([['id' => 1, 'code' => 'color']]);
        $this->assertTrue($this->installer->isSeeded());
    }

    public function testSeedInserts8CategoriesWhenEmpty(): void
    {
        $this->dao->method('listCategories')->willReturn([]);
        $this->dao->method('upsertCategory')->willReturn(1);
        $this->dao->expects($this->exactly(8))->method('upsertCategory');

        $result = $this->installer->seed();

        $this->assertEquals(8, $result['categories_added']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertGreaterThan(0, $result['values_added']);
    }

    public function testSeedSkipsExistingCategories(): void
    {
        // Simulate 3 categories already seeded
        $existing = [
            ['id' => 1, 'code' => 'opinion'],
            ['id' => 2, 'code' => 'size'],
            ['id' => 3, 'code' => 'age'],
        ];
        $this->dao->method('listCategories')->willReturn($existing);
        $this->dao->method('upsertCategory')->willReturn(4);

        // Only 5 categories should be inserted (8 - 3 existing)
        $this->dao->expects($this->exactly(5))->method('upsertCategory');

        $result = $this->installer->seed();

        $this->assertEquals(5, $result['categories_added']);
        $this->assertEquals(3, $result['skipped']);
    }

    public function testSeedCreatesValuesForEachNewCategory(): void
    {
        $this->dao->method('listCategories')->willReturn([]);
        $this->dao->method('upsertCategory')->willReturn(1);

        $valueCount = 0;
        $this->dao->method('upsertValue')->willReturnCallback(function() use (&$valueCount) {
            $valueCount++;
            return $valueCount;
        });

        $result = $this->installer->seed();

        // opinion(6) + size(8) + age(5) + shape(8) + color(10) + origin(8) + material(10) + purpose(8) = 63
        $this->assertEquals(63, $result['values_added']);
        $this->assertEquals(63, $valueCount);
    }

    public function testGetDefinitionsReturns8Entries(): void
    {
        $defs = $this->installer->getDefinitions();
        $this->assertCount(8, $defs);
    }

    public function testDefinitionsContainRoyalOrderCategories(): void
    {
        $defs  = $this->installer->getDefinitions();
        $codes = array_column($defs, 'code');

        $expected = ['opinion', 'size', 'age', 'shape', 'color', 'origin', 'material', 'purpose'];
        $this->assertEquals($expected, $codes);
    }

    public function testDefinitionsSortOrderIsSequential(): void
    {
        $defs = $this->installer->getDefinitions();
        foreach ($defs as $index => $def) {
            $this->assertEquals($index + 1, $def['sort_order']);
        }
    }

    public function testSeedWithAllCategoriesAlreadyPresentSkipsAll(): void
    {
        $existing = array_map(
            fn($d) => ['id' => $d['sort_order'], 'code' => $d['code']],
            $this->installer->getDefinitions()
        );
        $this->dao->method('listCategories')->willReturn($existing);
        $this->dao->expects($this->never())->method('upsertCategory');

        $result = $this->installer->seed();

        $this->assertEquals(0, $result['categories_added']);
        $this->assertEquals(8, $result['skipped']);
        $this->assertEquals(0, $result['values_added']);
    }
}
