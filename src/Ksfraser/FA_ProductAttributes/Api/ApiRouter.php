<?php

namespace Ksfraser\FA_ProductAttributes\Api;

use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * API router — maps HTTP method + path to the appropriate controller action.
 *
 * Supported routes:
 *   GET    /api/categories
 *   POST   /api/categories
 *   GET    /api/categories/{id}
 *   PUT    /api/categories/{id}
 *   DELETE /api/categories/{id}
 *   GET    /api/categories/{id}/values
 *   POST   /api/categories/{id}/values
 *   GET    /api/categories/{id}/values/{valId}
 *   PUT    /api/categories/{id}/values/{valId}
 *   DELETE /api/categories/{id}/values/{valId}
 *   GET    /api/products/{stockId}/assignments
 *   POST   /api/products/{stockId}/assignments
 *   PUT    /api/products/{stockId}/assignments
 *   GET    /api/products/{stockId}/assignments/{id}
 *   DELETE /api/products/{stockId}/assignments/{id}
 */
class ApiRouter
{
    /** @var ProductAttributesDao */
    private $dao;

    /** @var mixed */
    private $db;

    /** @var bool */
    private $testMode;

    public function __construct(ProductAttributesDao $dao, DbAdapterInterface $db, bool $testMode = false)
    {
        $this->dao      = $dao;
        $this->db       = $db;
        $this->testMode = $testMode;
    }

    /**
     * Dispatch a request.
     *
     * @param string $method  HTTP method (GET, POST, PUT, DELETE)
     * @param string $path    URL path, e.g. "/api/categories/1/values"
     */
    public function handle(string $method, string $path): void
    {
        // Strip leading /api/ prefix and split into parts
        $path      = ltrim($path, '/');
        $path      = preg_replace('#^api/#', '', $path);
        $pathParts = array_values(array_filter(explode('/', $path)));

        if (empty($pathParts)) {
            $this->sendError('Unknown resource', 404);
            return;
        }

        $resource = $pathParts[0];

        switch ($resource) {
            case 'categories':
                $this->handleCategories($method, array_slice($pathParts, 1));
                break;

            case 'products':
                $this->handleProducts($method, array_slice($pathParts, 1));
                break;

            default:
                $this->sendError('Unknown resource', 404);
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Private routing helpers
    // -------------------------------------------------------------------------

    private function handleCategories(string $method, array $parts): void
    {
        $controller = new CategoriesApiController($this->dao, $this->db, $this->testMode);

        // /categories
        if (count($parts) === 0) {
            if ($method === 'GET') {
                $controller->index();
            } elseif ($method === 'POST') {
                $controller->create();
            } else {
                $this->sendError('Method not allowed', 405);
            }
            return;
        }

        $categoryId = (int) $parts[0];

        // /categories/{id}/values/...
        if (isset($parts[1]) && $parts[1] === 'values') {
            $this->handleValues($method, $categoryId, array_slice($parts, 2));
            return;
        }

        // /categories/{id}
        if (count($parts) === 1) {
            if ($method === 'GET') {
                $controller->show($categoryId);
            } elseif ($method === 'PUT') {
                $controller->update($categoryId);
            } elseif ($method === 'DELETE') {
                $controller->delete($categoryId);
            } else {
                $this->sendError('Method not allowed', 405);
            }
            return;
        }

        $this->sendError('Unknown resource', 404);
    }

    private function handleValues(string $method, int $categoryId, array $parts): void
    {
        $controller = new ValuesApiController($this->dao, $this->db, $this->testMode);

        // /categories/{categoryId}/values
        if (count($parts) === 0) {
            if ($method === 'GET') {
                $controller->index($categoryId);
            } elseif ($method === 'POST') {
                $controller->create($categoryId);
            } else {
                $this->sendError('Method not allowed', 405);
            }
            return;
        }

        $valueId = (int) $parts[0];

        // /categories/{categoryId}/values/{id}
        if (count($parts) === 1) {
            if ($method === 'GET') {
                $controller->show($categoryId, $valueId);
            } elseif ($method === 'PUT') {
                $controller->update($categoryId, $valueId);
            } elseif ($method === 'DELETE') {
                $controller->delete($categoryId, $valueId);
            } else {
                $this->sendError('Method not allowed', 405);
            }
            return;
        }

        $this->sendError('Unknown resource', 404);
    }

    private function handleProducts(string $method, array $parts): void
    {
        if (empty($parts)) {
            $this->sendError('Missing stock_id', 400);
            return;
        }

        $stockId = $parts[0];

        // /products/{stockId}/assignments/...
        if (isset($parts[1]) && $parts[1] === 'assignments') {
            $this->handleAssignments($method, $stockId, array_slice($parts, 2));
            return;
        }

        $this->sendError('Unknown resource', 404);
    }

    private function handleAssignments(string $method, string $stockId, array $parts): void
    {
        $controller = new AssignmentsApiController($this->dao, $this->db, $this->testMode);

        // /products/{stockId}/assignments
        if (count($parts) === 0) {
            if ($method === 'GET') {
                $controller->index($stockId);
            } elseif ($method === 'POST') {
                $controller->create($stockId);
            } elseif ($method === 'PUT') {
                $controller->bulkUpdate($stockId);
            } else {
                $this->sendError('Method not allowed', 405);
            }
            return;
        }

        $id = (int) $parts[0];

        // /products/{stockId}/assignments/{id}
        if (count($parts) === 1) {
            if ($method === 'GET') {
                $controller->show($stockId, $id);
            } elseif ($method === 'DELETE') {
                $controller->delete($stockId, $id);
            } else {
                $this->sendError('Method not allowed', 405);
            }
            return;
        }

        $this->sendError('Unknown resource', 404);
    }

    private function sendError(string $message, int $statusCode): void
    {
        if (!$this->testMode) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
            exit;
        }

        // In test mode just emit the JSON body without header calls
        echo json_encode(['error' => $message]);
    }
}
