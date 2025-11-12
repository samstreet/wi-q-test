<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\GreatFoodConnector;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Services\MenuService;

/**
 * Integration test for Scenario 2: Update Product Name.
 *
 * This test validates the complete flow of:
 * 1. Authenticating with the Great Food Ltd API
 * 2. Updating a product's name via PUT request
 * 3. Validating the request contains correct headers and body
 * 4. Confirming the update was successful
 */
final class Scenario2Test extends TestCase
{
    /**
     * Test the complete Scenario 2 flow: Update product name from "Chpis" to "Chips".
     *
     * This test validates:
     * - OAuth2 authentication is performed correctly
     * - PUT request is made to correct endpoint
     * - Authorization header contains Bearer token
     * - Content-Type header is application/json
     * - Request body contains correct JSON: {"id": 84, "name": "Chips"}
     * - Update operation returns success
     */
    public function testUpdateProductNameFromChpisToChips(): void
    {
        // Track all HTTP requests made
        /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface, error: mixed, options: array<mixed>}> $historyContainer */
        $historyContainer = [];
        $history = Middleware::history($historyContainer);

        // Mock HTTP responses for the complete flow
        $mock = new MockHandler([
            // Response 1: OAuth2 token request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/token.json')
            ),
            // Response 2: Successful PUT request (HTTP 200 with empty body)
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([])
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);

        // Create PSR-17 factories and connector
        $httpFactory = new HttpFactory();
        $connector = new GreatFoodConnector(
            httpClient: $httpClient,
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            baseUrl: 'https://api.greatfood.example',
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret'
        );

        // Create service and execute Scenario 2
        $menuService = new MenuService($connector);

        // Create product with corrected name
        $product = new Product(id: 84, name: 'Chips');

        // Update product 84 in menu 7
        $result = $menuService->updateProduct(
            menuId: 7,
            productId: 84,
            product: $product
        );

        // Assert update was successful
        self::assertTrue($result);

        // Verify HTTP request history
        self::assertTrue(is_countable($historyContainer));
        self::assertCount(2, $historyContainer);

        // Verify request 1: OAuth2 token request
        $tokenRequest = $historyContainer[0]['request'];
        self::assertSame('POST', $tokenRequest->getMethod());
        self::assertStringContainsString('/auth_token', (string) $tokenRequest->getUri());

        // Verify request 2: PUT request to update product
        $updateRequest = $historyContainer[1]['request'];
        self::assertSame('PUT', $updateRequest->getMethod());
        self::assertStringContainsString('/menu/7/product/84', (string) $updateRequest->getUri());

        // Verify Authorization header is present with Bearer token
        self::assertTrue($updateRequest->hasHeader('Authorization'));
        self::assertStringContainsString('Bearer', $updateRequest->getHeaderLine('Authorization'));
        self::assertStringContainsString('33w4yh344go3u4h34yh93n4h3un4g34g', $updateRequest->getHeaderLine('Authorization'));

        // Verify Content-Type header is application/json
        self::assertTrue($updateRequest->hasHeader('Content-Type'));
        self::assertStringContainsString('application/json', $updateRequest->getHeaderLine('Content-Type'));

        // Verify request body contains correct JSON
        $requestBody = (string) $updateRequest->getBody();
        $decodedBody = json_decode($requestBody, true);
        self::assertIsArray($decodedBody);
        self::assertSame(84, $decodedBody['id']);
        self::assertSame('Chips', $decodedBody['name']);
    }

    /**
     * Test update with HTTP 204 No Content response.
     *
     * This test validates that the service handles HTTP 204 responses
     * correctly, as some APIs return 204 instead of 200 for successful
     * updates with no response body.
     */
    public function testUpdateProductWithNoContentResponse(): void
    {
        // Mock HTTP responses with 204 No Content
        $mock = new MockHandler([
            // Response 1: OAuth2 token request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/token.json')
            ),
            // Response 2: Successful PUT request (HTTP 204 No Content)
            new Response(204),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        // Create PSR-17 factories and connector
        $httpFactory = new HttpFactory();
        $connector = new GreatFoodConnector(
            httpClient: $httpClient,
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            baseUrl: 'https://api.greatfood.example',
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret'
        );

        // Create service and update product
        $menuService = new MenuService($connector);
        $product = new Product(id: 84, name: 'Chips');

        $result = $menuService->updateProduct(
            menuId: 7,
            productId: 84,
            product: $product
        );

        // Assert update was successful even with 204 response
        self::assertTrue($result);
    }

    /**
     * Test that multiple products can be updated in sequence.
     *
     * This test validates that the service can handle multiple
     * update operations in sequence, maintaining authentication
     * across requests.
     */
    public function testUpdateMultipleProducts(): void
    {
        // Track all HTTP requests made
        /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface, error: mixed, options: array<mixed>}> $historyContainer */
        $historyContainer = [];
        $history = Middleware::history($historyContainer);

        // Mock HTTP responses for multiple updates
        $mock = new MockHandler([
            // Response 1: OAuth2 token request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/token.json')
            ),
            // Response 2: First update (product 84)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([])),
            // Response 3: Second update (product 99)
            new Response(200, ['Content-Type' => 'application/json'], json_encode([])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);

        // Create PSR-17 factories and connector
        $httpFactory = new HttpFactory();
        $connector = new GreatFoodConnector(
            httpClient: $httpClient,
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            baseUrl: 'https://api.greatfood.example',
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret'
        );

        // Create service
        $menuService = new MenuService($connector);

        // Update first product
        $product1 = new Product(id: 84, name: 'Chips');
        $result1 = $menuService->updateProduct(menuId: 7, productId: 84, product: $product1);
        self::assertTrue($result1);

        // Update second product
        $product2 = new Product(id: 99, name: 'Lasagna');
        $result2 = $menuService->updateProduct(menuId: 7, productId: 99, product: $product2);
        self::assertTrue($result2);

        // Verify HTTP request history
        self::assertTrue(is_countable($historyContainer));
        self::assertCount(3, $historyContainer);

        // Verify only one authentication request was made
        self::assertTrue(is_array($historyContainer));
        $authRequests = array_filter(
            $historyContainer,
            fn ($transaction) => str_contains((string) $transaction['request']->getUri(), '/auth_token')
        );
        self::assertCount(1, $authRequests);

        // Verify two update requests were made
        $updateRequests = array_filter(
            $historyContainer,
            fn ($transaction) => $transaction['request']->getMethod() === 'PUT'
        );
        self::assertCount(2, $updateRequests);

        // Verify correct endpoints
        self::assertStringContainsString('/menu/7/product/84', (string) $historyContainer[1]['request']->getUri());
        self::assertStringContainsString('/menu/7/product/99', (string) $historyContainer[2]['request']->getUri());
    }

    /**
     * Test that authentication is performed automatically before update.
     *
     * This test validates that the MenuService automatically triggers
     * authentication before making update requests if not already authenticated.
     */
    public function testAuthenticationIsPerformedAutomaticallyForUpdate(): void
    {
        // Track all HTTP requests made
        /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface, error: mixed, options: array<mixed>}> $historyContainer */
        $historyContainer = [];
        $history = Middleware::history($historyContainer);

        // Mock HTTP responses
        $mock = new MockHandler([
            // Response 1: OAuth2 token request (should be automatic)
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/token.json')
            ),
            // Response 2: Successful PUT request
            new Response(200, ['Content-Type' => 'application/json'], json_encode([])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $httpClient = new Client(['handler' => $handlerStack]);

        // Create PSR-17 factories and connector
        $httpFactory = new HttpFactory();
        $connector = new GreatFoodConnector(
            httpClient: $httpClient,
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            baseUrl: 'https://api.greatfood.example',
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret'
        );

        // Verify connector is not authenticated initially
        self::assertFalse($connector->isAuthenticated());

        // Create service and make update request without explicitly authenticating
        $menuService = new MenuService($connector);
        $product = new Product(id: 84, name: 'Chips');
        $menuService->updateProduct(menuId: 7, productId: 84, product: $product);

        // Verify authentication happened automatically
        self::assertTrue($connector->isAuthenticated());
        self::assertTrue(is_countable($historyContainer));
        self::assertCount(2, $historyContainer);
        self::assertStringContainsString('/auth_token', (string) $historyContainer[0]['request']->getUri());
    }
}
