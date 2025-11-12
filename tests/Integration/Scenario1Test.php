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
use Tests\Fixtures\Models\Menu;
use Tests\Fixtures\Models\Product;
use Tests\Fixtures\Services\MenuService;

/**
 * Integration test for Scenario 1: Display Takeaway Menu Products.
 *
 * This test validates the complete flow of:
 * 1. Authenticating with the Great Food Ltd API
 * 2. Fetching all available menus
 * 3. Finding the "Takeaway" menu by name
 * 4. Retrieving products for that menu
 * 5. Validating the returned data matches expectations
 */
final class Scenario1Test extends TestCase
{
    /**
     * Test the complete Scenario 1 flow: Display Takeaway menu products.
     *
     * This test validates:
     * - OAuth2 authentication is performed correctly
     * - Authorization header is sent with Bearer token
     * - Menus are fetched and parsed correctly
     * - The "Takeaway" menu is found (ID: 3)
     * - Products are fetched for the correct menu
     * - Products are returned as Product model instances
     * - Product data matches expected values
     */
    public function testDisplayTakeawayMenuProducts(): void
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
            // Response 2: Get all menus request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/menus.json')
            ),
            // Response 3: Get products for Takeaway menu (ID: 3)
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/menu-products.json')
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

        // Create service and execute Scenario 1
        $menuService = new MenuService($connector);

        // Step 1: Find the Takeaway menu
        $takeawayMenu = $menuService->findMenuByName('Takeaway');

        // Assert the menu was found and has correct properties
        self::assertInstanceOf(Menu::class, $takeawayMenu);
        self::assertSame(3, $takeawayMenu->id);
        self::assertSame('Takeaway', $takeawayMenu->name);

        // Step 2: Get products for the Takeaway menu
        $products = $menuService->getMenuProducts($takeawayMenu->id);

        // Assert products were returned
        self::assertNotEmpty($products);
        self::assertCount(6, $products);

        // Assert all products are Product instances
        foreach ($products as $product) {
            self::assertInstanceOf(Product::class, $product);
        }

        // Validate specific products from the stub data
        self::assertSame(1, $products[0]->id);
        self::assertSame('Large Pizza', $products[0]->name);

        self::assertSame(3, $products[2]->id);
        self::assertSame('Burger', $products[2]->name);

        self::assertSame(4, $products[3]->id);
        self::assertSame('Chips', $products[3]->name);

        // Verify HTTP request history
        self::assertTrue(is_countable($historyContainer));
        self::assertCount(3, $historyContainer);

        // Verify request 1: OAuth2 token request
        $tokenRequest = $historyContainer[0]['request'];
        self::assertSame('POST', $tokenRequest->getMethod());
        self::assertStringContainsString('/auth_token', (string) $tokenRequest->getUri());

        // Verify request 2: Get menus request with Authorization header
        $menusRequest = $historyContainer[1]['request'];
        self::assertSame('GET', $menusRequest->getMethod());
        self::assertStringContainsString('/menus', (string) $menusRequest->getUri());
        self::assertTrue($menusRequest->hasHeader('Authorization'));
        self::assertStringContainsString('Bearer', $menusRequest->getHeaderLine('Authorization'));
        self::assertStringContainsString('33w4yh344go3u4h34yh93n4h3un4g34g', $menusRequest->getHeaderLine('Authorization'));

        // Verify request 3: Get menu products request with Authorization header
        $productsRequest = $historyContainer[2]['request'];
        self::assertSame('GET', $productsRequest->getMethod());
        self::assertStringContainsString('/menu/3/products', (string) $productsRequest->getUri());
        self::assertTrue($productsRequest->hasHeader('Authorization'));
        self::assertStringContainsString('Bearer', $productsRequest->getHeaderLine('Authorization'));
    }

    /**
     * Test edge case: Menu not found.
     *
     * This test validates that when searching for a non-existent menu,
     * the service returns null rather than throwing an exception.
     */
    public function testFindNonExistentMenuReturnsNull(): void
    {
        // Mock HTTP responses
        $mock = new MockHandler([
            // Response 1: OAuth2 token request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/token.json')
            ),
            // Response 2: Get all menus request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/menus.json')
            ),
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

        // Create service and search for non-existent menu
        $menuService = new MenuService($connector);
        $result = $menuService->findMenuByName('NonExistentMenu');

        // Assert null is returned for menu not found
        self::assertNull($result);
    }

    /**
     * Test that authentication is performed automatically when needed.
     *
     * This test validates that the MenuService automatically triggers
     * authentication before making API requests if not already authenticated.
     */
    public function testAuthenticationIsPerformedAutomatically(): void
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
            // Response 2: Get all menus request
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                file_get_contents(__DIR__ . '/../Fixtures/stubs/menus.json')
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

        // Verify connector is not authenticated initially
        self::assertFalse($connector->isAuthenticated());

        // Create service and make request without explicitly authenticating
        $menuService = new MenuService($connector);
        $menuService->getMenus();

        // Verify authentication happened automatically
        self::assertTrue($connector->isAuthenticated());
        self::assertTrue(is_countable($historyContainer));
        self::assertCount(2, $historyContainer);
        self::assertStringContainsString('/auth_token', (string) $historyContainer[0]['request']->getUri());
    }
}
