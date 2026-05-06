<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Variations\Actions\AssignParentAction;
use Ksfraser\FA_ProductAttributes\Variations\Dao\VariationsDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class AssignParentActionTest extends TestCase
{
    public function testHandleWithEmptyStockIdReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);

        $action = new AssignParentAction($dao, $db);
        $result = $action->handle(['stock_id' => '', 'assign_parent_stock_id' => 'PARENT']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleWithEmptyParentStockIdReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);

        $action = new AssignParentAction($dao, $db);
        $result = $action->handle(['stock_id' => 'CHILD', 'assign_parent_stock_id' => '']);

        $this->assertStringContainsString('Invalid', $result);
    }

    public function testHandleSameIdAsParentAndChildReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);

        $action = new AssignParentAction($dao, $db);
        $result = $action->handle(['stock_id' => 'P001', 'assign_parent_stock_id' => 'P001']);

        $this->assertStringContainsString('cannot be its own parent', $result);
    }

    public function testHandleWithMissingChildReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([]); // child not found

        $action = new AssignParentAction($dao, $db);
        $result = $action->handle(['stock_id' => 'CHILD', 'assign_parent_stock_id' => 'PARENT']);

        $this->assertStringContainsString('not found', $result);
    }

    public function testHandleWithMissingParentReturnsError(): void
    {
        $dao = $this->createMock(VariationsDao::class);

        $callCount = 0;
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                return $callCount === 1 ? [['1' => 1]] : []; // child exists, parent not found
            });

        $action = new AssignParentAction($dao, $db);
        $result = $action->handle(['stock_id' => 'CHILD', 'assign_parent_stock_id' => 'PARENT']);

        $this->assertStringContainsString('not found', $result);
    }

    public function testHandleSuccessfullyAssignsParent(): void
    {
        $dao = $this->createMock(VariationsDao::class);
        $dao->expects($this->once())
            ->method('setParentRelationship')
            ->with('SHIRT-RED', 'SHIRT');

        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('getTablePrefix')->willReturn('fa_');
        $db->method('query')->willReturn([['1' => 1]]); // both exist

        $action = new AssignParentAction($dao, $db);
        $result = $action->handle(['stock_id' => 'SHIRT-RED', 'assign_parent_stock_id' => 'SHIRT']);

        $this->assertStringContainsString('SHIRT-RED', $result);
        $this->assertStringContainsString('SHIRT', $result);
    }
}
