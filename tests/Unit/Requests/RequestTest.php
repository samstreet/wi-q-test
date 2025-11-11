<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use ApiClient\Http\HttpMethod;
use ApiClient\Requests\Request;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the abstract Request class.
 *
 * Tests that the Request abstract class provides the correct interface
 * and default implementations for concrete request classes to extend.
 */
final class RequestTest extends TestCase
{
    public function test_concrete_request_must_implement_resolve_endpoint(): void
    {
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

        $this->assertSame('/test/endpoint', $request->resolveEndpoint());
    }

    public function test_concrete_request_must_implement_method(): void
    {
        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::POST->value;
            }
        };

        $this->assertSame('POST', $request->method());
    }

    public function test_default_headers_returns_empty_array(): void
    {
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

        $this->assertSame([], $request->headers());
    }

    public function test_default_body_returns_empty_array(): void
    {
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

        $this->assertSame([], $request->body());
    }

    public function test_headers_can_be_overridden(): void
    {
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
                return [
                    'X-Custom-Header' => 'custom-value',
                    'X-Another-Header' => 'another-value',
                ];
            }
        };

        $headers = $request->headers();

        $this->assertCount(2, $headers);
        $this->assertSame('custom-value', $headers['X-Custom-Header']);
        $this->assertSame('another-value', $headers['X-Another-Header']);
    }

    public function test_body_can_be_overridden(): void
    {
        $request = new class () extends Request {
            public function resolveEndpoint(): string
            {
                return '/test';
            }

            public function method(): string
            {
                return HttpMethod::POST->value;
            }

            public function body(): array
            {
                return [
                    'name' => 'Test Name',
                    'email' => 'test@example.com',
                ];
            }
        };

        $body = $request->body();

        $this->assertCount(2, $body);
        $this->assertSame('Test Name', $body['name']);
        $this->assertSame('test@example.com', $body['email']);
    }

    public function test_request_with_path_parameters(): void
    {
        $request = new class (123) extends Request {
            public function __construct(private readonly int $id)
            {
            }

            public function resolveEndpoint(): string
            {
                return '/users/' . $this->id;
            }

            public function method(): string
            {
                return HttpMethod::GET->value;
            }
        };

        $this->assertSame('/users/123', $request->resolveEndpoint());
    }

    public function test_request_can_use_all_http_methods(): void
    {
        $methods = [
            HttpMethod::GET,
            HttpMethod::POST,
            HttpMethod::PUT,
            HttpMethod::PATCH,
            HttpMethod::DELETE,
            HttpMethod::HEAD,
            HttpMethod::OPTIONS,
        ];

        foreach ($methods as $httpMethod) {
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

            $this->assertSame($httpMethod->value, $request->method());
        }
    }
}
