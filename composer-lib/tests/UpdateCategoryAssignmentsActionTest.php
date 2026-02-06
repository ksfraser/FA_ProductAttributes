<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpdateCategoryAssignmentsAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class UpdateCategoryAssignmentsActionTest extends TestCase
{
    public function testHandleWithValidDataAddsAndRemovesAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        // Mock current assignments - product has categories 1 and 2
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST001')
            ->willReturn([
                ['id' => 1, 'category_id' => 1],
                ['id' => 2, 'category_id' => 2]
            ]);

        // Should remove category 2 and add category 3
        $dao->expects($this->once())
            ->method('removeCategoryAssignment')
            ->with('TEST001', 2);

        $dao->expects($this->once())
            ->method('addCategoryAssignment')
            ->with('TEST001', 3);

        $action = new UpdateCategoryAssignmentsAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST001',
            'category_ids' => ['1', '3'] // Keep 1, remove 2, add 3
        ]);

        $this->assertEquals("Category assignments updated for product 'TEST001'", $result);
    }

    public function testHandleWithNoChanges(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        // Mock current assignments - product has categories 1 and 2
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST001')
            ->willReturn([
                ['id' => 1, 'category_id' => 1],
                ['id' => 2, 'category_id' => 2]
            ]);

        // No changes expected
        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $dao->expects($this->never())
            ->method('addCategoryAssignment');

        $action = new UpdateCategoryAssignmentsAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST001',
            'category_ids' => ['1', '2'] // Same as current
        ]);

        $this->assertEquals("Category assignments updated for product 'TEST001'", $result);
    }

    public function testHandleWithEmptyCategoryIds(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        // Mock current assignments - product has categories 1 and 2
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST001')
            ->willReturn([
                ['id' => 1, 'category_id' => 1],
                ['id' => 2, 'category_id' => 2]
            ]);

        // Should remove both categories
        $dao->expects($this->exactly(2))
            ->method('removeCategoryAssignment')
            ->withConsecutive(
                ['TEST001', 1],
                ['TEST001', 2]
            );

        $dao->expects($this->never())
            ->method('addCategoryAssignment');

        $action = new UpdateCategoryAssignmentsAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST001',
            'category_ids' => [] // Remove all
        ]);

        $this->assertEquals("Category assignments updated for product 'TEST001'", $result);
    }

    public function testHandleWithNoExistingAssignments(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        // Mock no current assignments
        $dao->expects($this->once())
            ->method('listCategoryAssignments')
            ->with('TEST001')
            ->willReturn([]);

        // Should add categories 1 and 2
        $dao->expects($this->exactly(2))
            ->method('addCategoryAssignment')
            ->withConsecutive(
                ['TEST001', 1],
                ['TEST001', 2]
            );

        $dao->expects($this->never())
            ->method('removeCategoryAssignment');

        $action = new UpdateCategoryAssignmentsAction($dao);

        $result = $action->handle([
            'stock_id' => 'TEST001',
            'category_ids' => ['1', '2']
        ]);

        $this->assertEquals("Category assignments updated for product 'TEST001'", $result);
    }

    public function testHandleThrowsExceptionForMissingStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $action = new UpdateCategoryAssignmentsAction($dao);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Stock ID is required");

        $action->handle([
            'category_ids' => ['1', '2']
        ]);
    }

    public function testHandleThrowsExceptionForEmptyStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $action = new UpdateCategoryAssignmentsAction($dao);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Stock ID is required");

        $action->handle([
            'stock_id' => '',
            'category_ids' => ['1', '2']
        ]);
    }

    public function testHandleThrowsExceptionForWhitespaceStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);

        $action = new UpdateCategoryAssignmentsAction($dao);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Stock ID is required");

        $action->handle([
            'stock_id' => '   ',
            'category_ids' => ['1', '2']
        ]);
    }
}