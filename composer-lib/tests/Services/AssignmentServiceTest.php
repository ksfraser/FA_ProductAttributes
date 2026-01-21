<?php

namespace Ksfraser\FA_ProductAttributes\Services;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ksfraser\FA_ProductAttributes\Services\AssignmentService
 */
class AssignmentServiceTest extends TestCase
{
    /** @var MockObject|ProductAttributesDao */
    private $daoMock;

    /** @var MockObject|DbAdapterInterface */
    private $dbAdapterMock;

    /** @var AssignmentService */
    private $service;

    protected function setUp(): void
    {
        $this->daoMock = $this->createMock(ProductAttributesDao::class);
        $this->dbAdapterMock = $this->createMock(DbAdapterInterface::class);
        $this->service = new AssignmentService($this->daoMock, $this->dbAdapterMock);
    }

    public function testCreateAssignmentSuccess(): void
    {
        $input = [
            'stock_id' => 'WIDGET001',
            'category_id' => 1,
            'value_id' => 2,
            'sort_order' => 1
        ];

        $expectedAssignment = [
            'id' => 1,
            'stock_id' => 'WIDGET001',
            'category_id' => 1,
            'value_id' => 2,
            'sort_order' => 1
        ];

        $this->daoMock->expects($this->once())
            ->method('addAssignment')
            ->with('WIDGET001', 1, 2, 1);

        $this->daoMock->expects($this->once())
            ->method('listAssignments')
            ->with('WIDGET001')
            ->willReturn([$expectedAssignment]);

        $result = $this->service->createAssignment($input);

        $this->assertEquals($expectedAssignment, $result);
    }

    public function testCreateAssignmentValidationError(): void
    {
        $input = [
            'stock_id' => '',
            'category_id' => 1,
            'value_id' => 2
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock ID, category ID, and value ID are required');

        $this->service->createAssignment($input);
    }

    public function testDeleteAssignmentSuccess(): void
    {
        $assignmentId = 1;

        $this->daoMock->expects($this->once())
            ->method('deleteAssignment')
            ->with(1);

        $result = $this->service->deleteAssignment($assignmentId);

        $this->assertEquals([
            'deleted' => true,
            'message' => 'Assignment deleted successfully'
        ], $result);
    }

    public function testDeleteAssignmentInvalidId(): void
    {
        $assignmentId = 0;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Assignment ID must be greater than 0');

        $this->service->deleteAssignment($assignmentId);
    }

    public function testGetAssignmentsByStockId(): void
    {
        $stockId = 'WIDGET001';
        $expectedAssignments = [
            [
                'id' => 1,
                'stock_id' => 'WIDGET001',
                'category_id' => 1,
                'value_id' => 2,
                'sort_order' => 1,
                'category_code' => 'color',
                'category_label' => 'Color',
                'value_label' => 'Red',
                'value_slug' => 'red'
            ]
        ];

        $this->daoMock->expects($this->once())
            ->method('listAssignments')
            ->with('WIDGET001')
            ->willReturn($expectedAssignments);

        $result = $this->service->getAssignmentsByStockId($stockId);

        $this->assertEquals($expectedAssignments, $result);
    }
}