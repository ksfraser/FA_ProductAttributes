<?php

namespace Ksfraser\FA_ProductAttributes\Api;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\FA_ProductAttributes\Db\DbAdapterInterface;

/**
 * API Router for handling REST requests
 */
class ApiRouter
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var DbAdapterInterface */
    private $db;

    /** @var bool */
    private $testMode = false;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db, bool $testMode = false)
    {
        $this->dao = $dao;
        $this->db = $db;
        $this->testMode = $testMode;
    }

    /**
     * Handle API request
     * @param string $method
     * @param string $path
     */
    public function handle(string $method, string $path): void
    {
        // Remove leading slash and split path
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (empty($parts[0])) {
            $this->sendError('Invalid API path', 400);
        }

        $resource = $parts[0];

        switch ($resource) {
            case 'categories':
                $this->handleCategories($method, array_slice($parts, 1));
                break;
            case 'products':
                $this->handleProducts($method, array_slice($parts, 1));
                break;
            default:
                $this->sendError('Unknown resource: ' . $resource, 404);
        }
    }

    /**
     * Handle categories endpoints
     * @param string $method
     * @param array<int, string> $pathParts
     */
    private function handleCategories(string $method, array $pathParts): void
    {
        $controller = new CategoriesApiController($this->dao, $this->db, $this->testMode);

        if (empty($pathParts[0])) {
            // /api/categories
            switch ($method) {
                case 'GET':
                    $controller->index();
                    break;
                case 'POST':
                    $controller->create();
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } else {
            $id = (int) $pathParts[0];

            if (isset($pathParts[1]) && $pathParts[1] === 'values') {
                // /api/categories/{id}/values
                $this->handleValues($method, $id, array_slice($pathParts, 2));
                return;
            }

            // /api/categories/{id}
            switch ($method) {
                case 'GET':
                    $controller->show($id);
                    break;
                case 'PUT':
                    $controller->update($id);
                    break;
                case 'DELETE':
                    $controller->delete($id);
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        }
    }

    /**
     * Handle values endpoints
     * @param string $method
     * @param int $categoryId
     * @param array<int, string> $pathParts
     */
    private function handleValues(string $method, int $categoryId, array $pathParts): void
    {
        $controller = new ValuesApiController($this->dao, $this->db, $this->testMode);

        if (empty($pathParts[0])) {
            // /api/categories/{categoryId}/values
            switch ($method) {
                case 'GET':
                    $controller->index($categoryId);
                    break;
                case 'POST':
                    $controller->create($categoryId);
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } else {
            $id = (int) $pathParts[0];

            // /api/categories/{categoryId}/values/{id}
            switch ($method) {
                case 'GET':
                    $controller->show($categoryId, $id);
                    break;
                case 'PUT':
                    $controller->update($categoryId, $id);
                    break;
                case 'DELETE':
                    $controller->delete($categoryId, $id);
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        }
    }

    /**
     * Handle products endpoints
     * @param string $method
     * @param array<int, string> $pathParts
     */
    private function handleProducts(string $method, array $pathParts): void
    {
        if (empty($pathParts[0])) {
            $this->sendError('Product stock_id required', 400);
        }

        $stockId = $pathParts[0];

        if (isset($pathParts[1]) && $pathParts[1] === 'assignments') {
            // /api/products/{stockId}/assignments
            $this->handleAssignments($method, $stockId, array_slice($pathParts, 2));
            return;
        }

        $this->sendError('Unknown products endpoint', 404);
    }

    /**
     * Handle assignments endpoints
     * @param string $method
     * @param string $stockId
     * @param array<int, string> $pathParts
     */
    private function handleAssignments(string $method, string $stockId, array $pathParts): void
    {
        $controller = new AssignmentsApiController($this->dao, $this->db, $this->testMode);

        if (empty($pathParts[0])) {
            // /api/products/{stockId}/assignments
            switch ($method) {
                case 'GET':
                    $controller->index($stockId);
                    break;
                case 'POST':
                    $controller->create($stockId);
                    break;
                case 'PUT':
                    $controller->bulkUpdate($stockId);
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        } else {
            $id = (int) $pathParts[0];

            // /api/products/{stockId}/assignments/{id}
            switch ($method) {
                case 'GET':
                    $controller->show($stockId, $id);
                    break;
                case 'DELETE':
                    $controller->delete($stockId, $id);
                    break;
                default:
                    $this->sendError('Method not allowed', 405);
            }
        }
    }

    /**
     * Send error response
     * @param string $message
     * @param int $statusCode
     */
    private function sendError(string $message, int $statusCode = 400): void
    {
        if (!$this->testMode) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
        }
        echo json_encode(['error' => $message]);
        if (!$this->testMode) {
            exit;
        }
    }
}