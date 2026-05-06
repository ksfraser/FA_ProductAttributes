<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Service\VariationsDashboardService;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class VariationsDashboardServiceTest extends TestCase
{
    /** @var DbAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $db;

    /** @var VariationsDashboardService */
    private $service;

    protected function setUp(): void
    {
        $this->db = $this->createMock(DbAdapterInterface::class);
        $this->db->method('getTablePrefix')->willReturn('0_');
        $this->service = new VariationsDashboardService($this->db);
    }

    // ── getSummary ─────────────────────────────────────────────────────────────

    public function testGetSummaryReturnsStructuredResult(): void
    {
        $rows = [
            ['stock_id' => 'SHIRT', 'description' => 'T-Shirt', 'variation_count' => 6],
            ['stock_id' => 'PANTS', 'description' => 'Trousers', 'variation_count' => 4],
        ];

        $this->db->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls($rows, [['total' => 2]]);

        $result = $this->service->getSummary(1, 20);

        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('total_pages', $result);
        $this->assertCount(2, $result['rows']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['page']);
    }

    public function testGetSummaryPage1DefaultsCorrectly(): void
    {
        $this->db->method('query')->willReturn([]);
        $result = $this->service->getSummary();
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['per_page']);
    }

    public function testGetSummaryPageBelowOneClampedToOne(): void
    {
        $this->db->method('query')->willReturn([]);
        $result = $this->service->getSummary(-5, 20);
        $this->assertEquals(1, $result['page']);
    }

    public function testGetSummaryTotalPagesCalculated(): void
    {
        $this->db->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls([], [['total' => 45]]);

        $result = $this->service->getSummary(1, 20);
        $this->assertEquals(3, $result['total_pages']); // ceil(45/20)
    }

    // ── getTotalProductCount ───────────────────────────────────────────────────

    public function testGetTotalProductCountReturnsInteger(): void
    {
        $this->db->method('query')->willReturn([['total' => 12]]);
        $this->assertEquals(12, $this->service->getTotalProductCount());
    }

    public function testGetTotalProductCountReturnsZeroWhenEmpty(): void
    {
        $this->db->method('query')->willReturn([]);
        $this->assertEquals(0, $this->service->getTotalProductCount());
    }

    // ── filterByCategory ──────────────────────────────────────────────────────

    public function testFilterByCategoryPassesCategoryIdToQuery(): void
    {
        $expected = [['stock_id' => 'SHIRT', 'description' => 'T-Shirt', 'variation_count' => 3]];

        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('category_id'),
                $this->arrayHasKey('category_id')
            )
            ->willReturn($expected);

        $result = $this->service->filterByCategory(5);
        $this->assertEquals($expected, $result);
    }

    public function testFilterByCategoryReturnsEmptyArrayWhenNoMatches(): void
    {
        $this->db->method('query')->willReturn([]);
        $this->assertEquals([], $this->service->filterByCategory(99));
    }

    // ── filterByVariationCount ────────────────────────────────────────────────

    public function testFilterByVariationCountNoUpperLimit(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('variation_count >= :min'),
                $this->logicalNot($this->arrayHasKey('max'))
            )
            ->willReturn([]);

        $this->service->filterByVariationCount(2, 0);
    }

    public function testFilterByVariationCountWithUpperLimit(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains(':max'),
                $this->arrayHasKey('max')
            )
            ->willReturn([]);

        $this->service->filterByVariationCount(2, 10);
    }

    public function testFilterByVariationCountReturnsMatchingRows(): void
    {
        $rows = [['stock_id' => 'PROD1', 'description' => 'Product A', 'variation_count' => 5]];
        $this->db->method('query')->willReturn($rows);

        $result = $this->service->filterByVariationCount(3, 8);
        $this->assertCount(1, $result);
        $this->assertEquals('PROD1', $result[0]['stock_id']);
    }

    // ── filterByStockStatus ───────────────────────────────────────────────────

    public function testFilterByStockStatusActivePassesZero(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->anything(),
                ['inactive' => 0]
            )
            ->willReturn([]);

        $this->service->filterByStockStatus(false);
    }

    public function testFilterByStockStatusInactivePassesOne(): void
    {
        $this->db->expects($this->once())
            ->method('query')
            ->with(
                $this->anything(),
                ['inactive' => 1]
            )
            ->willReturn([]);

        $this->service->filterByStockStatus(true);
    }
}
