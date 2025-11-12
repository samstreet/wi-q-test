<?php

declare(strict_types=1);

/**
 * Scenario 2: Update Product Name
 *
 * This example demonstrates how to use the wi-Q REST API client library
 * with the Great Food Ltd API to update a product's name. Specifically,
 * this script updates product 84 in menu 7 from "Chpis" to "Chips".
 *
 * Usage:
 *   php examples/scenario2.php
 *
 * Requirements:
 *   - Composer autoloader must be available
 *   - Configure API credentials below
 *   - Great Food Ltd API must be accessible
 *   - Product 84 must exist in menu 7
 *
 * Flow:
 *   1. Initialize the GreatFoodConnector with API credentials
 *   2. Create MenuService instance
 *   3. Authenticate with the API (automatic via MenuService)
 *   4. Create a Product model with the corrected name
 *   5. Send PUT request to update the product
 *   6. Display success confirmation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Tests\Fixtures\GreatFoodConnector;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Services\MenuService;

// ============================================================================
// Configuration - Update these values for your API environment
// ============================================================================

$baseUrl = 'https://api.greatfood.example.com';
$clientId = '1337';
$clientSecret = '4j3g4gj304gj3';

// Product details to update
$menuId = 7;
$productId = 84;
$oldName = 'Chpis';
$newName = 'Chips';

// ============================================================================
// Main Script
// ============================================================================

try {
    echo sprintf("Updating product %d in menu %d...\n\n", $productId, $menuId);

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

    // Create product model with corrected name
    // Note: This will automatically trigger authentication if not already authenticated
    $updatedProduct = new Product(
        id: $productId,
        name: $newName,
    );

    // Update the product via the API
    $success = $menuService->updateProduct(
        menuId: $menuId,
        productId: $productId,
        product: $updatedProduct,
    );

    if ($success) {
        echo sprintf("✓ Successfully updated product name from \"%s\" to \"%s\"\n", $oldName, $newName);
        echo "API request completed with status: 200\n";
        exit(0);
    } else {
        echo sprintf("✗ Failed to update product %d in menu %d\n", $productId, $menuId);
        exit(1);
    }
} catch (RuntimeException $e) {
    echo "API Error: {$e->getMessage()}\n";
    exit(1);
} catch (Throwable $e) {
    echo "Unexpected Error: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
