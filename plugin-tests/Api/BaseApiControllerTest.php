<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\BaseApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class BaseApiControllerTest extends TestCase
{
    /** @var BaseApiController|\PHPUnit\Framework\MockObject\MockObject */
    private $controller;

    protected function setUp(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db  = $this->createMock(DbAdapterInterface::class);
        $this->controller = $this->getMockForAbstractClass(
            BaseApiController::class,
            [$dao, $db, true]
        );
    }

    public function testJsonResponseEncodesData(): void
    {
        ob_start();
        $ref = new \ReflectionMethod(BaseApiController::class, 'jsonResponse');
        $ref->setAccessible(true);
        $ref->invoke($this->controller, ['key' => 'value'], 200);
        $output = ob_get_clean();

        $this->assertJson($output);
        $this->assertStringContainsString('"key":"value"', $output);
    }

    public function testErrorResponseContainsErrorKey(): void
    {
        ob_start();
        $ref = new \ReflectionMethod(BaseApiController::class, 'errorResponse');
        $ref->setAccessible(true);
        $ref->invoke($this->controller, 'Not found', 404);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Not found"', $output);
    }

    public function testValidateRequiredReturnsTrueWhenAllPresent(): void
    {
        $ref = new \ReflectionMethod(BaseApiController::class, 'validateRequired');
        $ref->setAccessible(true);
        $result = $ref->invoke($this->controller, ['a' => '1', 'b' => '2'], ['a', 'b']);
        $this->assertTrue($result);
    }

    public function testValidateRequiredReturnsFalseWhenMissing(): void
    {
        $ref = new \ReflectionMethod(BaseApiController::class, 'validateRequired');
        $ref->setAccessible(true);
        $result = $ref->invoke($this->controller, ['a' => '1'], ['a', 'b']);
        $this->assertFalse($result);
    }

    public function testValidateRequiredReturnsFalseWhenEmpty(): void
    {
        $ref = new \ReflectionMethod(BaseApiController::class, 'validateRequired');
        $ref->setAccessible(true);
        $result = $ref->invoke($this->controller, ['a' => ''], ['a']);
        $this->assertFalse($result);
    }
}
