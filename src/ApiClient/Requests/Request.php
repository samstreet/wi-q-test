<?php

declare(strict_types=1);

namespace ApiClient\Requests;

/**
 * Abstract base class for all API requests.
 *
 * This class provides a generic structure for API requests that can be used
 * with any REST API. Concrete implementations must define the endpoint and
 * HTTP method, while headers and body are optional.
 *
 * Example usage:
 * <code>
 * class GetUsersRequest extends Request
 * {
 *     public function resolveEndpoint(): string
 *     {
 *         return '/users';
 *     }
 *
 *     public function method(): string
 *     {
 *         return HttpMethod::GET->value;
 *     }
 * }
 * </code>
 */
abstract class Request
{
    /**
     * Resolve the endpoint for this request.
     *
     * This method should return the endpoint path (without the base URL)
     * for this specific request. The path should start with a forward slash.
     *
     * @return string The endpoint path (e.g., '/users', '/products/123')
     */
    abstract public function resolveEndpoint(): string;

    /**
     * Get the HTTP method for this request.
     *
     * This method should return the HTTP method as a string.
     * It's recommended to use HttpMethod enum values for type safety.
     *
     * @return string The HTTP method (e.g., 'GET', 'POST', 'PUT')
     */
    abstract public function method(): string;

    /**
     * Get additional headers for this request.
     *
     * This method can be overridden to provide request-specific headers.
     * Headers returned here will be merged with default headers from the connector.
     *
     * @return array<string, string> Associative array of header name => value pairs
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * Get the request body.
     *
     * This method can be overridden to provide a request body for POST, PUT,
     * PATCH requests. The array will be encoded as JSON when sent.
     *
     * @return array<string, mixed> The request body as an associative array
     */
    public function body(): array
    {
        return [];
    }
}
