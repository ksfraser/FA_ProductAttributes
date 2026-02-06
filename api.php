<?php

// Simple API endpoint for Product Attributes tab
// This handles AJAX requests from the embedded tab interface

error_reporting(E_ALL);
ini_set('display_errors', 1);

$path_to_root = realpath(__DIR__ . '/../../');
if ($path_to_root === false) {
    $path_to_root = '../..';
}

include($path_to_root . "/includes/session.inc");

add_security_extensions();
add_access_extensions();

use Ksfraser\ModulesDAO\Factory\DatabaseAdapterFactory;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;

header('Content-Type: application/json');

try {
    $db_adapter = DatabaseAdapterFactory::create('fa');
    $dao = new ProductAttributesDao($db_adapter);

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_values':
            $categoryId = (int)($_GET['category_id'] ?? 0);
            if ($categoryId > 0) {
                $values = $dao->listValues($categoryId);
                echo json_encode($values);
            } else {
                echo json_encode([]);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}