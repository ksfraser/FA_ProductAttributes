<?php

namespace Ksfraser\FA_ProductAttributes\Test\UI;

use Ksfraser\FA_ProductAttributes\UI\VariationsDashboardTab;
use Ksfraser\FA_ProductAttributes\Service\VariationsDashboardService;
use PHPUnit\Framework\TestCase;

class VariationsDashboardTabTest extends TestCase
{
    /** @var VariationsDashboardService|\PHPUnit\Framework\MockObject\MockObject */
    private $service;

    /** @var VariationsDashboardTab */
    private $tab;

    protected function setUp(): void
    {
        $this->service = $this->createMock(VariationsDashboardService::class);
        $this->tab     = new VariationsDashboardTab($this->service);

        // Ensure a clean $_GET for each test
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
    }

    public function testRenderOutputsHeading(): void
    {
        $this->service->method('getSummary')->willReturn([
            'rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 0,
        ]);

        ob_start();
        $this->tab->render();
        $html = ob_get_clean();

        $this->assertStringContainsString('Variations Dashboard', $html);
    }

    public function testRenderShowsEmptyMessageWhenNoRows(): void
    {
        $this->service->method('getSummary')->willReturn([
            'rows' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 0,
        ]);

        ob_start();
        $this->tab->render();
        $html = ob_get_clean();

        $this->assertStringContainsString('No products found', $html);
    }

    public function testRenderOutputsTableRowsWhenResultsPresent(): void
    {
        $rows = [
            ['stock_id' => 'SHIRT', 'description' => 'T-Shirt', 'variation_count' => 6],
            ['stock_id' => 'PANTS', 'description' => 'Trousers', 'variation_count' => 4],
        ];
        $this->service->method('getSummary')->willReturn([
            'rows' => $rows, 'total' => 2, 'page' => 1, 'per_page' => 20, 'total_pages' => 1,
        ]);

        ob_start();
        $this->tab->render();
        $html = ob_get_clean();

        $this->assertStringContainsString('SHIRT', $html);
        $this->assertStringContainsString('T-Shirt', $html);
        $this->assertStringContainsString('PANTS', $html);
        $this->assertStringContainsString('Trousers', $html);
    }

    public function testRenderFilterByCategoryCallsCorrectServiceMethod(): void
    {
        $_GET['dash_filter'] = 'category';
        $_GET['dash_cat']    = '3';

        $this->service->expects($this->once())
            ->method('filterByCategory')
            ->with(3)
            ->willReturn([]);

        $this->service->expects($this->never())->method('getSummary');

        ob_start();
        $this->tab->render();
        ob_end_clean();
    }

    public function testRenderFilterByCountCallsCorrectServiceMethod(): void
    {
        $_GET['dash_filter'] = 'count';
        $_GET['dash_min']    = '2';
        $_GET['dash_max']    = '8';

        $this->service->expects($this->once())
            ->method('filterByVariationCount')
            ->with(2, 8)
            ->willReturn([]);

        ob_start();
        $this->tab->render();
        ob_end_clean();
    }

    public function testRenderFilterByInactiveCallsCorrectServiceMethod(): void
    {
        $_GET['dash_filter'] = 'inactive';

        $this->service->expects($this->once())
            ->method('filterByStockStatus')
            ->with(true)
            ->willReturn([]);

        ob_start();
        $this->tab->render();
        ob_end_clean();
    }

    public function testRenderEscapesStockIdInOutput(): void
    {
        $rows = [
            ['stock_id' => '<script>xss</script>', 'description' => 'Dangerous', 'variation_count' => 1],
        ];
        $this->service->method('getSummary')->willReturn([
            'rows' => $rows, 'total' => 1, 'page' => 1, 'per_page' => 20, 'total_pages' => 1,
        ]);

        ob_start();
        $this->tab->render();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderShowsPaginationWhenMultiplePages(): void
    {
        $this->service->method('getSummary')->willReturn([
            'rows' => [['stock_id' => 'P1', 'description' => 'Prod', 'variation_count' => 2]],
            'total'       => 50,
            'page'        => 1,
            'per_page'    => 20,
            'total_pages' => 3,
        ]);

        ob_start();
        $this->tab->render();
        $html = ob_get_clean();

        $this->assertStringContainsString('dash_page=2', $html);
        $this->assertStringContainsString('dash_page=3', $html);
    }
}
