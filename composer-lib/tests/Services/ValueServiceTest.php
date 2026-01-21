<?php

namespace Ksfraser\FA_ProductAttributes\Services;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ksfraser\FA_ProductAttributes\Services\ValueService
 */
class ValueServiceTest extends TestCase
{
    /** @var MockObject|ProductAttributesDao */
    private $daoMock;

    /** @var MockObject|DbAdapterInterface */
    private $dbAdapterMock;

    /** @var ValueService */
    private $service;

    protected function setUp(): void
    {
        $this->daoMock = $this->createMock(ProductAttributesDao::class);
        $this->dbAdapterMock = $this->createMock(DbAdapterInterface::class);
        $this->service = new ValueService($this->daoMock, $this->dbAdapterMock);
    }

    public function testCreateValueSuccess(): void
    {
        $input = [
            'category_id' => 1,
            'value' => 'Red',
            'slug' => 'red',
            'sort_order' => 1,
            'active' => true
        ];

        $expectedValue = [
            'id' => 1,
            'category_id' => 1,
            'value' => 'Red',
            'slug' => 'red',
            'sort_order' => 1,
            'active' => 1
        ];

        $this->daoMock->expects($this->exactly(2))
            ->method('listValues')
            ->with(1)
            ->willReturnOnConsecutiveCalls([], [$expectedValue]);

        $result = $this->service->createValue($input);

        $this->assertEquals($expectedValue, $result);
    }

    public function testCreateValueValidationError(): void
    {
        $input = [
            'category_id' => 1,
            'value' => '',
            'slug' => 'red'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value is required');

        $this->service->createValue($input);
    }

    public function testCreateValueDuplicateValue(): void
    {
        $input = [
            'category_id' => 1,
            'value' => 'Red',
            'slug' => 'red'
        ];

        $existingValues = [
            ['id' => 1, 'value' => 'Red', 'slug' => 'red']
        ];

        $this->daoMock->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn($existingValues);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value 'Red' already exists in this category. Use update to modify it.");

        $this->service->createValue($input);
    }

    public function testUpdateValueSuccess(): void
    {
        $valueId = 1;
        $input = [
            'category_id' => 1,
            'value' => 'Blue',
            'slug' => 'blue',
            'sort_order' => 2,
            'active' => false
        ];

        $existingValues = [
            ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red']
        ];

        $updatedValue = [
            'id' => 1,
            'category_id' => 1,
            'value' => 'Blue',
            'slug' => 'blue',
            'sort_order' => 2,
            'active' => 0
        ];

        $this->daoMock->expects($this->exactly(2))
            ->method('listValues')
            ->with(1)
            ->willReturnOnConsecutiveCalls($existingValues, [$updatedValue]);

        $this->daoMock->expects($this->once())
            ->method('upsertValue')
            ->with(1, 'Blue', 'blue', 2, false, 1);

        $result = $this->service->updateValue($valueId, $input);

        $this->assertEquals($updatedValue, $result);
    }

    public function testUpdateValueNotFound(): void
    {
        $valueId = 999;
        $input = ['category_id' => 1, 'value' => 'Blue'];

        $this->daoMock->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([['id' => 1, 'value' => 'Red']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value not found');

        $this->service->updateValue($valueId, $input);
    }

    public function testDeleteValueHardDelete(): void
    {
        $valueId = 1;

        $categories = [
            ['id' => 1, 'code' => 'color', 'label' => 'Color']
        ];

        $existingValues = [
            ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red']
        ];

        $this->daoMock->expects($this->once())
            ->method('listCategories')
            ->willReturn($categories);

        $this->daoMock->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn($existingValues);

        $this->dbAdapterMock->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');

        $this->dbAdapterMock->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE value_id = :value_id', ['value_id' => 1])
            ->willReturn([['count' => 0]]);

        $this->daoMock->expects($this->once())
            ->method('deleteValue')
            ->with(1);

        $result = $this->service->deleteValue($valueId);

        $this->assertEquals([
            'deleted' => true,
            'hard_delete' => true,
            'message' => 'Value deleted successfully'
        ], $result);
    }

    public function testDeleteValueSoftDelete(): void
    {
        $valueId = 1;

        $categories = [
            ['id' => 1, 'code' => 'color', 'label' => 'Color']
        ];

        $existingValues = [
            ['id' => 1, 'category_id' => 1, 'value' => 'Red', 'slug' => 'red', 'sort_order' => 1, 'active' => 1]
        ];

        $this->daoMock->expects($this->once())
            ->method('listCategories')
            ->willReturn($categories);

        $this->daoMock->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn($existingValues);

        $this->dbAdapterMock->expects($this->once())
            ->method('getTablePrefix')
            ->willReturn('fa_');

        $this->dbAdapterMock->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as count FROM `fa_product_attribute_assignments` WHERE value_id = :value_id', ['value_id' => 1])
            ->willReturn([['count' => 1]]);

        $this->daoMock->expects($this->once())
            ->method('upsertValue')
            ->with(1, 'Red', 'red', 1, false, 1);

        $result = $this->service->deleteValue($valueId);

        $this->assertEquals([
            'deleted' => true,
            'hard_delete' => false,
            'message' => 'Value deactivated successfully (in use by products)'
        ], $result);
    }
}