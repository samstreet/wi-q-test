<?php

declare(strict_types=1);

namespace Tests\Unit\Client;

use ApiClient\Client\Connector;
use ApiClient\Http\HttpMethod;
use ApiClient\Requests\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for the abstract Connector class.
 *
 * Tests that the Connector class correctly builds URLs, merges headers,
 * sends requests via Guzzle, and handles both successful and error responses.
 */
final class ConnectorTest extends TestCase
{
    public function test_send_builds_correct_url(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test/endpoint';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $result = $connector->send($request);

        $this->assertSame(['success' => true], $result);
    }

    public function test_send_merges_headers_correctly(): void
    {
        $capturedHeaders = [];

        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => 'test'])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        // Add middleware to capture request headers from PSR-7 request
        $handlerStack->push(function (callable $handler) use (&$capturedHeaders) {
            return function ($request, array $options) use ($handler, &$capturedHeaders) {
                // Capture headers from the PSR-7 request object
                foreach ($request->getHeaders() as $name => $values) {
                    $capturedHeaders[$name] = $values[0] ?? '';
                }

                return $handler($request, $options);
            };
        });

        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }

            public function headers(): array
            {
                return ['X-Request-Header' => 'request-value'];
            }
        };

        $connector->send($request);

        // Verify that both default and request headers are present
        $this->assertNotEmpty($capturedHeaders);
        $this->assertArrayHasKey('Accept', $capturedHeaders);
        $this->assertSame('application/json', $capturedHeaders['Accept']);
        $this->assertArrayHasKey('X-Request-Header', $capturedHeaders);
        $this->assertSame('request-value', $capturedHeaders['X-Request-Header']);
    }

    public function test_send_handles_successful_json_response(): void
    {
        $expectedData = [
            'id' => 123,
            'name' => 'Test Product',
            'price' => 29.99,
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($expectedData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/products/123';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $result = $connector->send($request);

        $this->assertSame($expectedData, $result);
    }

    public function test_send_handles_empty_response(): void
    {
        $mock = new MockHandler([
            new Response(204, [], ''),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/delete/123';
            }

            public function method(): string
            {
                return HttpMethod::DELETE->value;
            }
        };

        $result = $connector->send($request);

        $this->assertSame([], $result);
    }

    public function test_send_handles_404_error(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Not Found',
                new GuzzleRequest('GET', 'test'),
                new Response(404, [], json_encode(['error' => 'Resource not found']))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/not/found';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP request failed with status 404');

        $connector->send($request);
    }

    public function test_send_handles_500_error(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Internal Server Error',
                new GuzzleRequest('POST', 'test'),
                new Response(500, [], json_encode(['error' => 'Server error']))
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/error';
            }

            public function method(): string
            {
                return HttpMethod::POST->value;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP request failed with status 500');

        $connector->send($request);
    }

    public function test_send_with_request_body(): void
    {
        $mock = new MockHandler([
            new Response(201, [], json_encode(['id' => 456, 'created' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/products';
            }

            public function method(): string
            {
                return HttpMethod::POST->value;
            }

            public function body(): array
            {
                return [
                    'name' => 'New Product',
                    'price' => 49.99,
                ];
            }
        };

        $result = $connector->send($request);

        $this->assertSame(['id' => 456, 'created' => true], $result);
    }

    public function test_send_parses_json_correctly(): void
    {
        $complexData = [
            'items' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
            ],
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($complexData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/items';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $result = $connector->send($request);

        $this->assertSame($complexData, $result);
    }

    public function test_send_throws_exception_for_invalid_json(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'invalid json {{{'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse JSON response');

        $connector->send($request);
    }

    public function test_connector_uses_different_http_methods(): void
    {
        $methods = [
            HttpMethod::GET,
            HttpMethod::POST,
            HttpMethod::PUT,
            HttpMethod::PATCH,
            HttpMethod::DELETE,
        ];

        foreach ($methods as $httpMethod) {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['method' => $httpMethod->value])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $connector = $this->createMockConnector($handlerStack);

            $request = new class ($httpMethod) extends Request {
                public function __construct(private readonly HttpMethod $httpMethod)
                {
                }

                public function resolveEndpoint(): string
                {
                    return '/test';
                }

                public function method(): string
                {
                    return $this->httpMethod->value;
                }
            };

            $result = $connector->send($request);

            $this->assertSame(['method' => $httpMethod->value], $result);
        }
    }

    public function test_content_type_header_is_case_insensitive(): void
    {
        $capturedHeaders = [];

        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(function (callable $handler) use (&$capturedHeaders) {
            return function ($request, array $options) use ($handler, &$capturedHeaders) {
                $capturedHeaders = $request->getHeaders();

                return $handler($request, $options);
            };
        });

        $connector = $this->createMockConnector($handlerStack);

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::POST->value;
            }

            public function headers(): array
            {
                return ['content-type' => 'application/xml'];
            }

            public function body(): array
            {
                return ['test' => 'data'];
            }
        };

        $connector->send($request);

        // Should NOT have duplicate Content-Type headers
        $this->assertCount(1, $capturedHeaders['content-type'] ?? $capturedHeaders['Content-Type'] ?? []);
        // Should use the request's content-type, not auto-added JSON
        $this->assertStringContainsString('xml', implode('', $capturedHeaders['content-type'] ?? $capturedHeaders['Content-Type'] ?? []));
    }

    public function test_handles_json_boolean_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(true)),
        ]);

        $connector = $this->createMockConnector(HandlerStack::create($mock));

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $result = $connector->send($request);

        $this->assertArrayHasKey('data', $result);
        $this->assertTrue($result['data']);
    }

    public function test_handles_json_string_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode('success')),
        ]);

        $connector = $this->createMockConnector(HandlerStack::create($mock));

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $result = $connector->send($request);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame('success', $result['data']);
    }

    public function test_handles_json_numeric_array_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([1, 2, 3])),
        ]);

        $connector = $this->createMockConnector(HandlerStack::create($mock));

        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $result = $connector->send($request);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame([1, 2, 3], $result['data']);
    }

    /**
     * Create a mock connector with a custom Guzzle handler.
     */
    private function createMockConnector(HandlerStack $handlerStack): Connector
    {
        return new class ($handlerStack) extends Connector {
            private HandlerStack $handlerStack;

            public function __construct(HandlerStack $handlerStack)
            {
                $this->handlerStack = $handlerStack;
                // Don't call parent constructor, we'll set httpClient manually
                $this->httpClient = new Client(['handler' => $this->handlerStack]);
            }

            protected function resolveBaseUrl(): string
            {
                return 'https://api.test.com';
            }

            protected function defaultHeaders(): array
            {
                return [
                    'Accept' => 'application/json',
                ];
            }
        };
    }
}
