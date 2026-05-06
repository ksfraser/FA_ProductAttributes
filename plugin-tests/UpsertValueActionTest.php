<?php

namespace Ksfraser\FA_ProductAttributes\Test\Actions;

use Ksfraser\FA_ProductAttributes\Actions\UpsertValueAction;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use PHPUnit\Framework\TestCase;

class UpsertValueActionTest extends TestCase
{
    public function testHandleCreateWithValidData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([]);
        $dao->expects($this->once())
            ->method('upsertValue')
            ->with(123, 'Red', 'red', 1, true, 0);

        $action = new UpsertValueAction($dao);

        $result = $action->handle([
            'category_id' => '123',
            'value' => 'Red',
            'slug' => 'red',
            'sort_order' => '1',
            'active' => 'on'
        ]);

        $this->assertEquals("Value saved successfully", $result);
    }

    public function testHandleUpdateWithValidData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([
                ['id' => 456, 'value' => 'Red', 'slug' => 'red']
            ]);
        $dao->expects($this->once())
            ->method('upsertValue')
            ->with(123, 'Blue', 'blue', 2, false, 456);

        $action = new UpsertValueAction($dao);

        $result = $action->handle([
            'category_id' => '123',
            'value_id' => '456',
            'value' => 'Blue',
            'slug' => 'blue',
            'sort_order' => '2'
        ]);

        $this->assertEquals("Value updated successfully", $result);
    }

    public function testHandleWithMissingCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listValues');
        $dao->expects($this->never())
            ->method('upsertValue');

        $action = new UpsertValueAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category ID is required");

        $action->handle([
            'value' => 'Red',
            'slug' => 'red'
        ]);
    }

    public function testHandleWithInvalidCategoryId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listValues');
        $dao->expects($this->never())
            ->method('upsertValue');

        $action = new UpsertValueAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Category ID is required");

        $action->handle([
            'category_id' => '0',
            'value' => 'Red',
            'slug' => 'red'
        ]);
    }

    public function testHandleWithMissingValue(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->never())
            ->method('listValues');
        $dao->expects($this->never())
            ->method('upsertValue');

        $action = new UpsertValueAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value is required");

        $action->handle([
            'category_id' => '123',
            'slug' => 'red'
        ]);
    }

    public function testHandleCreateWithDuplicateValue(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([
                ['id' => 789, 'value' => 'Red', 'slug' => 'red']
            ]);
        $dao->expects($this->never())
            ->method('upsertValue');

        $action = new UpsertValueAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value 'Red' already exists in this category. Use Edit to modify it.");

        $action->handle([
            'category_id' => '123',
            'value' => 'Red',
            'slug' => 'red'
        ]);
    }

    public function testHandleUpdateWithDuplicateValue(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $dao->expects($this->once())
            ->method('listValues')
            ->with(123)
            ->willReturn([
                ['id' => 456, 'value' => 'Old Red', 'slug' => 'old-red'],
                ['id' => 789, 'value' => 'Red', 'slug' => 'red']
            ]);
        $dao->expects($this->never())
            ->method('upsertValue');

        $action = new UpsertValueAction($dao);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value 'Red' already exists in this category");

        $action->handle([
            'category_id' => '123',
            'value_id' => '456',
            'value' => 'Red',
            'slug' => 'red'
        ]);
    }
}