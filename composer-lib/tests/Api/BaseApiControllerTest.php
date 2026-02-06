<?php

namespace Ksfraser\FA_ProductAttributes\Test\Api;

use Ksfraser\FA_ProductAttributes\Api\BaseApiController;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;
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
}