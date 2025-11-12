<?php

declare(strict_types=1);

namespace ApiClient\Client;

use ApiClient\Requests\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * Abstract base connector for API communication.
 *
 * This class provides generic HTTP communication functionality that can be used
 * with any REST API. Concrete implementations must provide the base URL and
 * default headers specific to their API.
 *
 * Example usage:
 * <code>
 * class MyApiConnector extends Connector
 * {
 *     public function __construct(
 *         ClientInterface $httpClient,
 *         RequestFactoryInterface $requestFactory,
 *         StreamFactoryInterface $streamFactory,
 *         private readonly string $apiKey,
 *     ) {
 *         parent::__construct($httpClient, $requestFactory, $streamFactory);
 *     }
 *
 *     protected function resolveBaseUrl(): string
 *     {
 *         return 'https://api.example.com';
 *     }
 *
 *     protected function defaultHeaders(): array
 *     {
 *         return [
 *             'Accept' => 'application/json',
 *             'Authorization' => 'Bearer ' . $this->apiKey,
 *         ];
 *     }
 * }
 *
 * // Usage with any PSR-18 client
 * $connector = new MyApiConnector(
 *     $httpClient,      // Any PSR-18 implementation
 *     $requestFactory,  // Any PSR-17 request factory
 *     $streamFactory,   // Any PSR-17 stream factory
 *     'my-api-key'
 * );
 * </code>
 */
abstract class Connector
{
    /**
     * Initialize the connector with PSR-18 HTTP client and factories.
     *
     * @param ClientInterface $httpClient PSR-18 compliant HTTP client
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     */
    public function __construct(
        protected ClientInterface $httpClient,
        protected RequestFactoryInterface $requestFactory,
        protected StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Resolve the base URL for API requests.
     *
     * This method should return the base URL for the API (without trailing slash).
     * All request endpoints will be appended to this base URL.
     *
     * @return string The base URL (e.g., 'https://api.example.com')
     */
    abstract protected function resolveBaseUrl(): string;

    /**
     * Get default headers for all requests.
     *
     * This method should return headers that will be included in every request.
     * Common examples include authentication headers, Accept headers, etc.
     *
     * @return array<string, string> Associative array of header name => value pairs
     */
    abstract protected function defaultHeaders(): array;

    /**
     * Send a request to the API.
     *
     * @param Request $request The request to send
     * @return array<string, mixed> The parsed JSON response
     * @throws RuntimeException If the request fails or response cannot be parsed
     */
    public function send(Request $request): array
    {
        try {
            // Build the full URL
            $url = $this->resolveBaseUrl() . $request->resolveEndpoint();

            // Merge headers
            $headers = array_merge($this->defaultHeaders(), $request->headers());

            // Get request body
            $body = $request->body();

            // Set Content-Type header if body is present (case-insensitive check)
            if ($body !== []) {
                $headerKeys = array_change_key_case($headers, CASE_LOWER);
                if (!isset($headerKeys['content-type'])) {
                    $headers['Content-Type'] = 'application/json';
                }
            }

            // Create PSR-7 request
            $psrRequest = $this->requestFactory->createRequest($request->method(), $url);

            // Add headers to PSR-7 request
            foreach ($headers as $name => $value) {
                $psrRequest = $psrRequest->withHeader($name, $value);
            }

            // Add body if present
            if ($body !== []) {
                $jsonBody = json_encode($body);
                if ($jsonBody === false) {
                    throw new RuntimeException('Failed to encode request body as JSON: ' . json_last_error_msg());
                }
                $stream = $this->streamFactory->createStream($jsonBody);
                $psrRequest = $psrRequest->withBody($stream);
            }

            // Send the request using PSR-18 client
            $response = $this->httpClient->sendRequest($psrRequest);

            // Get response body
            $responseBody = (string) $response->getBody();

            // Handle empty responses
            if ($responseBody === '') {
                return [];
            }

            // Parse JSON response
            /** @var mixed $decoded */
            $decoded = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(
                    'Failed to parse JSON response: ' . json_last_error_msg()
                );
            }

            // Handle non-associative arrays and scalar values
            if (!is_array($decoded) || array_is_list($decoded)) {
                /** @var array<string, mixed> */
                return ['data' => $decoded];
            }

            /** @var array<string, mixed> $decoded */
            return $decoded;
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            // Check if the exception has response information (for HTTP errors)
            // Many implementations (like Guzzle) provide hasResponse() and getResponse()
            if (method_exists($e, 'hasResponse') && method_exists($e, 'getResponse') && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $responseBody = (string) $response->getBody();

                throw new RuntimeException(
                    sprintf(
                        'HTTP request failed with status %d: %s',
                        $statusCode,
                        $responseBody !== '' ? $responseBody : $e->getMessage()
                    ),
                    $statusCode,
                    $e
                );
            }

            // Handle other client exceptions (network errors, etc.)
            throw new RuntimeException(
                sprintf('Request failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
