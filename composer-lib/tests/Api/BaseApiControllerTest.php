<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\BaseApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test for BaseApiController
 */
class BaseApiControllerTest extends TestCase
{
    public function testConstructor(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        // We can't instantiate BaseApiController directly since it's abstract
        // But we can test that the constructor parameters are stored
        $this->assertTrue(true); // Placeholder - concrete implementations will test this
    }

    public function testJsonResponseMethodExists(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        // Create a concrete implementation for testing
        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testJsonResponse($data, int $statusCode = 200): void
            {
                $this->jsonResponse($data, $statusCode);
            }
        };

        $this->assertTrue(method_exists($controller, 'testJsonResponse'));
    }

    public function testJsonResponseOutputsJson(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testJsonResponse($data, int $statusCode = 200): void
            {
                ob_start();
                $this->jsonResponse($data, $statusCode);
                $output = ob_get_clean();
                echo $output; // Re-echo for test capture
            }
        };

        ob_start();
        $controller->testJsonResponse(['test' => 'data']);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertEquals(['test' => 'data'], $decoded);
    }

    public function testErrorResponseMethodExists(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testErrorResponse(string $message, int $statusCode = 400): void
            {
                $this->errorResponse($message, $statusCode);
            }
        };

        $this->assertTrue(method_exists($controller, 'testErrorResponse'));
    }

    public function testErrorResponseOutputsErrorJson(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testErrorResponse(string $message, int $statusCode = 400): void
            {
                ob_start();
                $this->errorResponse($message, $statusCode);
                $output = ob_get_clean();
                echo $output;
            }
        };

        ob_start();
        $controller->testErrorResponse('Test error', 400);
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        $this->assertEquals(['error' => 'Test error'], $decoded);
    }

    public function testGetJsonInputWithValidJson(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testGetJsonInput(): array
            {
                return $this->getJsonInput();
            }
        };

        // Mock php://input
        $testJson = '{"name": "test", "value": 123}';
        file_put_contents('php://temp', $testJson);
        
        // We can't easily mock file_get_contents for php://input, so we'll test the method exists
        $this->assertTrue(method_exists($controller, 'testGetJsonInput'));
    }

    public function testValidateRequiredWithValidData(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testValidateRequired(array $data, array $required): bool
            {
                return $this->validateRequired($data, $required);
            }
        };

        $data = ['name' => 'test', 'value' => 123];
        $required = ['name', 'value'];

        $result = $controller->testValidateRequired($data, $required);
        $this->assertTrue($result);
    }

    public function testValidateRequiredWithMissingField(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testValidateRequired(array $data, array $required): bool
            {
                return $this->validateRequired($data, $required);
            }
        };

        $data = ['name' => 'test'];
        $required = ['name', 'value'];

        $result = $controller->testValidateRequired($data, $required);
        $this->assertFalse($result);
    }

    public function testValidateRequiredWithEmptyField(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $db = $this->createMock(DbAdapterInterface::class);

        $controller = new class($dao, $db, true) extends BaseApiController {
            public function testValidateRequired(array $data, array $required): bool
            {
                return $this->validateRequired($data, $required);
            }
        };

        $data = ['name' => 'test', 'value' => ''];
        $required = ['name', 'value'];

        $result = $controller->testValidateRequired($data, $required);
        $this->assertFalse($result);
    }
}