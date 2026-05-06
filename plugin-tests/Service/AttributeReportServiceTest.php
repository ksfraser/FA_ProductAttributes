<?php

namespace Ksfraser\FA_ProductAttributes\Test\Service;

use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\FA_ProductAttributes\Variations\Service\AttributeReportService;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class AttributeReportServiceTest extends TestCase
{
    // ------------------------------------------------------------------ BR7

    public function testGetProductsWithAttributesReturnsEmptyWhenNoParents(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getParentStockIds')->willReturn([]);

        $db      = $this->createMock(DbAdapterInterface::class);
        $service = new AttributeReportService($dao, $db);

        $this->assertSame([], $service->getProductsWithAttributes());
    }

    public function testGetProductsWithAttributesReturnsProductsThatHaveAssignments(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getParentStockIds')
            ->willReturn([
                ['stock_id' => 'SHIRT'],
                ['stock_id' => 'PANTS'],
            ]);

        $dao->method('listAssignments')
            ->willReturnCallback(function (string $stockId) {
                if ($stockId === 'SHIRT') {
                    return [['category_id' => 1, 'value_id' => 10]];
                }
                return []; // PANTS has no assignments
            });

        $db      = $this->createMock(DbAdapterInterface::class);
        $service = new AttributeReportService($dao, $db);

        $result = $service->getProductsWithAttributes();

        $this->assertCount(1, $result);
        $this->assertSame('SHIRT', $result[0]['stock_id']);
        $this->assertCount(1, $result[0]['assignments']);
    }

    public function testGetProductsWithAttributesSkipsEmptyStockId(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->method('getParentStockIds')
            ->willReturn([['stock_id' => '']]);

        $db      = $this->createMock(DbAdapterInterface::class);
        $service = new AttributeReportService($dao, $db);

        $this->assertSame([], $service->getProductsWithAttributes());
    }

    // ------------------------------------------------------------------ BR7.1

    public function testValidateInactiveParentsReturnsEmptyWhenNoInconsistencies(): void
    {
        $dao = $this->createMock(VariationsDao::class);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([]);

        $service = new AttributeReportService($dao, $db);

        $this->assertSame([], $service->validateInactiveParents());
    }

    public function testValidateInactiveParentsReturnsInconsistentRows(): void
    {
        $dao = $this->createMock(VariationsDao::class);

        $dbRows = [
            ['parent_stock_id' => 'SHIRT', 'variation_stock_id' => 'SHIRT-RED', 'qty_on_hand' => 3],
        ];

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn($dbRows);

        $service = new AttributeReportService($dao, $db);
        $result  = $service->validateInactiveParents();

        $this->assertCount(1, $result);
        $this->assertSame('SHIRT', $result[0]['parent_stock_id']);
        $this->assertSame('SHIRT-RED', $result[0]['variation_stock_id']);
        $this->assertSame(3, $result[0]['qty_on_hand']);
    }

    public function testValidateInactiveParentsQueriesCorrectTablePrefix(): void
    {
        $dao = $this->createMock(VariationsDao::class);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('myprefix_');
        $db->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('myprefix_stock_master'),
                $this->anything()
            )
            ->willReturn([]);

        $service = new AttributeReportService($dao, $db);
        $service->validateInactiveParents();
    }
}
