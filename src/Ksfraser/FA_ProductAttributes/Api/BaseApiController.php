<?php

namespace Ksfraser\FA_ProductAttributes\Api;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Base API controller with common JSON response and validation helpers.
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
        $this->dao      = $dao;
        $this->db       = $db;
        $this->testMode = $testMode;
    }

    /**
     * Send a JSON response.
     * @param mixed $data
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
     * Send an error JSON response.
     */
    protected function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['error' => $message], $statusCode);
    }

    /**
     * Read and decode the request body as JSON.
     * @return array<string, mixed>
     */
    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data  = json_decode((string)$input, true);
        return $data ?: [];
    }

    /**
     * Check that all required keys are present and non-empty.
     * @param array<string, mixed> $data
     * @param array<int, string>   $required
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
