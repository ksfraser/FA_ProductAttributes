<?php

namespace Ksfraser\FA_ProductAttributes\Api;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * Base API controller with common functionality
 */
abstract class BaseApiController
{
    /** @var ProductAttributesDao */
    protected $dao;

    /** @var DbAdapterInterface */
    protected $db;

    /** @var bool */
    protected $testMode = false;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db, bool $testMode = false)
    {
        $this->dao = $dao;
        $this->db = $db;
        $this->testMode = $testMode;
    }

    /**
     * Send JSON response
     * @param mixed $data
     * @param int $statusCode
     */
    protected function jsonResponse($data, int $statusCode = 200): void
    {
        if (!$this->testMode) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
        }
        echo json_encode($data);
        if (!$this->testMode) {
            exit;
        }
    }

    /**
     * Send error response
     * @param string $message
     * @param int $statusCode
     */
    protected function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['error' => $message], $statusCode);
        // In test mode, don't throw exceptions - just output JSON for testing
    }

    /**
     * Get JSON input from request body
     * @return array<string, mixed>
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return $data ?: [];
    }

    /**
     * Validate required fields
     * @param array<string, mixed> $data
     * @param array<int, string> $required
     * @return bool
     */
    protected function validateRequired(array $data, array $required): bool
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return false;
            }
        }
        return true;
    }
}