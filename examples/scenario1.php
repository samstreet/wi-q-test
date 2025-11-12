<?php

declare(strict_types=1);

/**
 * Scenario 1: Display Takeaway Menu Products
 *
 * This example demonstrates how to use the wi-Q REST API client library
 * with the Great Food Ltd API to fetch and display products from the
 * "Takeaway" menu.
 *
 * Usage:
 *   php examples/scenario1.php
 *
 * Requirements:
 *   - Composer autoloader must be available
 *   - Configure API credentials below
 *   - Great Food Ltd API must be accessible
 *
 * Flow:
 *   1. Initialize the GreatFoodConnector with API credentials
 *   2. Create MenuService instance
 *   3. Authenticate with the API (automatic via MenuService)
 *   4. Find the "Takeaway" menu by name
 *   5. Fetch all products for that menu
 *   6. Display products in a formatted table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Tests\Fixtures\GreatFoodConnector;
use Tests\Fixtures\Services\MenuService;

// ============================================================================
// Configuration - Update these values for your API environment
// ============================================================================

$baseUrl = 'https://api.greatfood.example.com';
$clientId = '1337';
$clientSecret = '4j3g4gj304gj3';

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Format products as a table matching the expected output format.
 *
 * @param array<int, \Tests\Fixtures\Models\Product> $products
 * @return string
 */
function formatProductTable(array $products): string
{
    $output = "| ID | Name        |\n";
    $output .= "| -- | ----------- |\n";

    foreach ($products as $product) {
        $output .= sprintf("| %-2d | %-11s |\n", $product->id, $product->name);
    }

    return $output;
}

// ============================================================================
// Main Script
// ============================================================================

try {
    echo "Fetching Takeaway menu products...\n\n";

    // Initialize HTTP client and PSR-17 factories
    $httpClient = new Client([
        'timeout' => 30.0,
        'verify' => true,
    ]);
    $httpFactory = new HttpFactory();

    // Create Great Food API connector
    $connector = new GreatFoodConnector(
        httpClient: $httpClient,
        requestFactory: $httpFactory,
        streamFactory: $httpFactory,
        baseUrl: $baseUrl,
        clientId: $clientId,
        clientSecret: $clientSecret,
    );

    // Create menu service
    $menuService = new MenuService($connector);

    // Find the "Takeaway" menu
    // Note: This will automatically trigger authentication if not already authenticated
    $takeawayMenu = $menuService->findMenuByName('Takeaway');

    if ($takeawayMenu === null) {
        echo "Error: Takeaway menu not found.\n";
        echo "Available menus can be checked by inspecting the API response.\n";
        exit(1);
    }

    // Fetch products for the Takeaway menu
    $products = $menuService->getMenuProducts($takeawayMenu->id);

    if (empty($products)) {
        echo "No products found in the Takeaway menu.\n";
        exit(0);
    }

    // Display products in table format
    echo formatProductTable($products);
    echo "\n";
    echo sprintf("Successfully displayed %d products from Takeaway menu.\n", count($products));

    exit(0);
} catch (RuntimeException $e) {
    echo "API Error: {$e->getMessage()}\n";
    exit(1);
} catch (Throwable $e) {
    echo "Unexpected Error: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
